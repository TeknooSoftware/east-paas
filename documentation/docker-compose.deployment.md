Teknoo Software - PaaS library - Docker Compose deployment driver
=================================================================

Introduction
------------

Besides the historical `Kubernetes` driver, `East PaaS` ships a second, orchestrator-agnostic cluster
deployment driver targeting a plain **Docker host**. Instead of talking to a live orchestrator API, this
driver:

1. transcribes the immutable `CompiledDeployment` model into a single **Compose Specification** file
   (`compose.yaml`) plus a **Traefik v3** dynamic-configuration file (`<project>.yml`);
2. connects to the remote Docker host **over SSH** and applies everything by running **Ansible playbooks**
   (one per stage), mirroring the way the `Image` infrastructure runs `buildah`/`docker` through Symfony
   Process.

The driver is registered in the `Cluster\Directory` under the type string **`docker-compose`**.

The PHP namespace is `Teknoo\East\Paas\Infrastructures\DockerCompose\` mapped to
`infrastructures/DockerCompose/`.

> **Image building is unchanged.** Only the **deploy** and **expose** stages are new. OCI images are still
> built and pushed by the `Image` infrastructure exactly as for Kubernetes.

How the driver works
--------------------

The driver is a [Teknoo States](https://github.com/TeknooSoftware/states) proxy, with the same two-state
shape as the Kubernetes driver:

* **`Generator`** — the instance created by the DI container (the "mother"). It refuses to run and throws a
  `GeneratorStateException`; it only knows how to clone itself.
* **`Running`** — the configured "daughter" instance, available only inside a workplan after `configure()`
  has been called with the cluster URL, identity, defaults bag and namespace.

`configure()` rejects any identity that is not a `Teknoo\East\Paas\Object\ClusterCredentials`
(`UnsupportedIdentityException`). The `$useHierarchicalNamespaces` flag of the `DriverInterface` contract is
**stored but ignored**: hierarchical namespaces are a Kubernetes-only concept and have no meaning for Docker
Compose.

Deployment pipeline:

```
CompiledDeployment ─► DockerCompose\Driver (Generator → Running)
                        │  deploy():  transcribers tagged Generic / Deployment
                        │  expose():  transcribers tagged Exposing
                        ▼
                     Generation (in-memory accumulator)
                        • compose: services / networks / volumes / configs / secrets
                        • traefik: http / tcp / udp routers + services, tls
                        • files:   map<path, content> (secrets, configs, TLS certs)
                        • networksToWire: string[]
                        ▼
                     Driver serializes the accumulator to YAML, writes the playbook,
                     a single-host inventory and all pushed files into a per-run temp dir
                        ▼
                     RunnerInterface (AnsibleRunner / SymfonyProcessRunner) ── SSH ─► Docker host
                        deploy.yml : create dirs, push files, `docker compose -p <project> up -d`,
                                     run during-deployment jobs, `docker network connect <net> traefik`
                        expose.yml : push TLS files + `<project>.yml` into Traefik's watched directory
                        ▼
                     promise->success(['compose' => …, 'traefik' => …, 'output' => …])
                        └─► flows into the job History (Deploying:Result / Exposing:Result)
```

Unlike Kubernetes (where each manifest is `apply()`-ed immediately against a live `Client`), the Compose
transcribers only **accumulate** into a `Generation` builder. The driver then serializes the accumulator
(`Symfony\Component\Yaml\Yaml::dump($array, 8, 4)` — inline depth 8, 4-space indent) and runs
`ansible-playbook` **once per stage**.

The `success` payload contains only the generated `compose` / `traefik` arrays plus the Ansible stdout
summary. Sensitive file contents (secret values, TLS private keys) live only in the accumulator's
`files` map; they are written to the per-run temp dir and pushed by Ansible, but are **never** serialized
into the job History.

Cluster configuration
---------------------

In a project's cluster definition (the same place Kubernetes clusters are declared), set:

| Field       | Value                                                                                  |
|-------------|----------------------------------------------------------------------------------------|
| `type`      | `docker-compose`                                                                       |
| `address`   | the SSH address of the Docker host, e.g. `ssh://deployer@docker-host.example.com:22`   |
| `namespace` | the environment namespace (combined with the project name to form the Compose project) |
| `identity`  | a `ClusterCredentials` carrying the SSH login and private key (see mapping below)      |

### `cluster.address` formats

The address is parsed into an Ansible single-host inventory. The accepted formats are:

* `ssh://user@host:port`
* `host:port`
* `host` (port defaults to **22**)

The SSH user embedded in `ssh://user@host` is used as a fallback when `ClusterCredentials::getUsername()`
is empty.

### `ClusterCredentials` → SSH / Ansible mapping

The driver reuses the existing `Teknoo\East\Paas\Object\ClusterCredentials` object (no new domain object).
Its getters are mapped to SSH / Ansible as follows:

| `ClusterCredentials` getter | SSH / Ansible use                                                                                                              |
|-----------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `getUsername()`             | `ansible_user` (SSH login). If empty, falls back to the user in `cluster.address`.                                             |
| `getClientKey()`            | SSH **private key**. Written to a temp file with mode `0600`, then passed as `ansible_ssh_private_key_file` (`--private-key`). |
| `getPassword()`             | optional SSH password / become (`--ask-become-pass`) value.                                                                    |
| `getCaCertificate()`        | optional host public key for `known_hosts` (strict host-key checking).                                                         |
| `getToken()`                | **unused** by this driver.                                                                                                     |
| `getClientCertificate()`    | **unused** by this driver.                                                                                                     |

The private key is materialized to a temporary file by `RunnerFactory` (Ansible refuses world-readable
keys, hence `chmod 0600`) and the temp files are removed in the factory's `__destruct()`, mirroring the
write/unlink discipline of the Kubernetes `Factory`.

Compose project & network naming
--------------------------------

* **Compose project name** (`docker compose -p <project>`) =
  `sanitizeDns("{namespace}-{projectName}")` where `namespace` is the cluster's `namespace` and
  `projectName` comes from `CompiledDeployment::withJobSettings()`. This isolates both the **project** and
  the **environment** (the cluster already carries the per-environment namespace). `sanitizeDns()`
  lowercases the value and replaces every character outside `[a-z0-9-]` with `-`, collapsing repeats and
  trimming leading/trailing `-`.

* **Dedicated network.** Each project gets a dedicated network declared inside the Compose file, named
  `private` by default. Compose auto-prefixes it with the project name on the host, so the real network is
  `"{project}_private"`. It uses the `bridge` driver (configurable) with `internal: true`, so containers
  cannot reach the host or the outside world unless a service explicitly publishes a host port. External
  reachability is provided **only** through Traefik.

* **Connect-per-project.** The driver records the resolved private network name (`wireNetworkToTraefik()`)
  so the deploy playbook runs `docker network connect <project>_private <traefik-container>`, making Traefik
  join every dedicated project network. The command is idempotent (an "already exists in network" error is
  ignored). See `documentation/traefik.ingress.md` for the Traefik side.

What gets generated
-------------------

For each run the driver writes, into a fresh per-run working directory under
`teknoo.east.paas.worker.tmp_dir`:

* `compose.yaml` — the Compose Specification file (`services`, `networks`, `volumes`, `configs`, `secrets`).
* `<project>.yml` — **expose stage only** — the Traefik v3 dynamic-configuration file.
* `deploy.yml` / `expose.yml` — the rendered Ansible playbook for the stage.
* `inventory.ini` — the single-host inventory built from `cluster.address`.
* secret / config / TLS cert files referenced by the Compose and Traefik configs.

The `CompiledDeployment` model is mapped to the Compose / Traefik model by a priority-ordered set of
transcribers:

| Priority | Transcriber             | Stage  | Role                                                                |
|----------|-------------------------|--------|---------------------------------------------------------------------|
| 5        | `NetworkTranscriber`    | deploy | declares the dedicated `private` network; wires it to Traefik       |
| 10       | `SecretTranscriber`     | deploy | PaaS `Secret` → Compose `secrets` + pushed files                    |
| 10       | `ConfigMapTranscriber`  | deploy | PaaS `Map` → Compose `configs`                                      |
| 10       | `VolumeTranscriber`     | deploy | persistent / secret / map volumes                                   |
| 30       | `DeploymentTranscriber` | deploy | pods → Compose services (one anchor + sidecars sharing the network) |
| 32       | `JobTranscriber`        | deploy | during-deployment jobs only (Compose `jobs` profile)                |
| 40       | `ServiceTranscriber`    | expose | network aliases; external raw TCP/UDP → Traefik TCP/UDP routers     |
| 50       | `IngressTranscriber`    | expose | HTTP(S) ingresses → Traefik routers/services + TLS                  |

### Pods → services

* A **single-container pod** becomes one Compose service named after the pod (so `Service.podName`
  resolves).
* A **multi-container pod** becomes an **anchor** service (the first container, named after the pod) plus one
  service per sidecar using `network_mode: "service:<anchor>"`, so they share the pod's localhost and port
  space (replicating Kubernetes pod network sharing). Shared pod volumes are mounted on each.
* Container ports map to Compose `expose:` (intra-network only — no host `ports:` for internal services),
  health checks map to Compose `healthcheck`, resource requirements to `deploy.resources.reservations` /
  `deploy.resources.limits`, replicas to `deploy.replicas`, and the restart policy to `restart`.

Persistent-volume size limitation
---------------------------------

`PersistentVolumeInterface` volumes become **named Compose volumes** backed by the **`local`** driver.

> **Limitation.** The `storageSize` declared in `.paas.yaml` is **advisory only** — the local Compose volume
> driver does **not** enforce a size quota, unlike a Kubernetes `PersistentVolumeClaim`. Likewise
> `allowWriteMany` is a **no-op** on a single host (there is only one node). Volumes flagged with
> `resetOnDeployment` are removed by the deploy playbook (`community.docker.docker_volume: state=absent`)
> **before** the stack is brought up.

If you need an enforced quota, pre-provision the volume on the host (e.g. a dedicated filesystem or an
external volume driver) and reference it.

During-deployment jobs vs scheduled jobs
----------------------------------------

`East PaaS` jobs have a `planning`:

* **During-deployment** jobs (`Planning::DuringDeployment`) are emitted into the Compose file as services
  under a Compose **profile** named `jobs` with `restart: "no"`, so a plain `docker compose up` does **not**
  start them. The deploy playbook runs each with
  `docker compose -p <project> --profile jobs run --rm <svc>` and checks the exit codes (honouring
  `isParallel`, `completionsCount`, the configured success/failure exit codes and `timeLimit`).

* **Scheduled** jobs (`Planning::Scheduled`) are **not** written into the Compose file. The local Docker
  host has no native cron equivalent of a Kubernetes `CronJob`, so scheduled jobs are handled
  **platform-side**: the East PaaS worker re-dispatches them on time (via `symfony/scheduler`). Kubernetes
  is unaffected and keeps using its native `CronJob`.

Ansible playbooks
-----------------

The two playbook templates live in `infrastructures/DockerCompose/templates/`. `{% … %}` placeholders are
substituted before execution (project name, host paths, Traefik container, etc.).

**`deploy.yml.template`:**

1. ensure the per-project directory (and `secrets/`, `configs/` subdirs) exists on the host;
2. push `compose.yaml`, secret files (mode `0600`/`0700`) and config files;
3. remove `resetOnDeployment` volumes (`community.docker.docker_volume: state=absent`);
4. `docker compose -p <project> up -d --remove-orphans`;
5. run during-deployment jobs (`docker compose -p <project> --profile jobs run --rm <svc>`);
6. `docker network connect <project>_private <traefik-container>` — idempotent.

**`expose.yml.template`:**

1. push the TLS cert/key files into the Traefik certs directory (mode `0600`);
2. push the `<project>.yml` Traefik dynamic file into Traefik's **watched directory**. Traefik
   (`watch: true`) reloads automatically — no restart.

The single-host **inventory** is generated per run from `cluster.address` (`ansible_host`, `ansible_port`);
the SSH user and private key are applied by the runner from the `ClusterCredentials`.

Configuration (DI parameters)
-----------------------------

The driver reads the following container parameters (all optional, with the defaults shown):

| Parameter                                                            | Default                | Purpose                                        |
|----------------------------------------------------------------------|------------------------|------------------------------------------------|
| `teknoo.east.paas.worker.tmp_dir`                                    | system temp dir        | per-run working directory + SSH key temp file  |
| `teknoo.east.paas.docker-compose.ansible.binary`                     | `ansible-playbook`     | Ansible playbook binary                        |
| `teknoo.east.paas.docker-compose.timeout`                            | library default (300s) | Ansible run timeout (seconds)                  |
| `teknoo.east.paas.docker-compose.deploy_root`                        | `/opt/paas`            | host root for per-project deploy dirs          |
| `teknoo.east.paas.docker-compose.network.driver`                     | `bridge`               | dedicated network driver                       |
| `teknoo.east.paas.docker-compose.traefik.container`                  | `traefik`              | Traefik container name/id to `network connect` |
| `teknoo.east.paas.docker-compose.traefik.dynamic_dir`                | `/etc/traefik/dynamic` | Traefik watched directory                      |
| `teknoo.east.paas.docker-compose.traefik.certs_dir`                  | `/etc/traefik/certs`   | host dir for pushed TLS cert/key files         |
| `teknoo.east.paas.docker-compose.traefik.default_certresolver`       | _(none)_               | ACME resolver name for `meta.letsencrypt`      |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.web`             | `web`                  | HTTP entrypoint name                           |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.websecure`       | `websecure`            | HTTPS entrypoint name                          |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.tcp`             | `tcp`                  | raw-TCP entrypoint name                        |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.udp`             | `udp`                  | raw-UDP entrypoint name                        |
| `teknoo.east.paas.docker-compose.https_backend.insecure_skip_verify` | `false`                | skip TLS verification to HTTPS backends        |

See `documentation/traefik.ingress.md` for the Traefik static configuration that must be in place on the
host.

Host prerequisites
------------------

On the **worker** (where the deployment runs):

* `ansible` / `ansible-playbook` available on `PATH` (or pointed to via
  `teknoo.east.paas.docker-compose.ansible.binary`);
* the `community.docker` Ansible collection (used for `community.docker.docker_volume`);
* the optional `asm/php-ansible` Composer package (the `AnsibleRunner` wraps it; a raw Symfony Process
  `SymfonyProcessRunner` is provided as a swappable fallback selected by the DI).

On the **Docker host** (the `cluster.address` target):

* **Docker Engine + Compose v2** (`docker compose …`, not the legacy `docker-compose`);
* **SSH** access for the mapped `ClusterCredentials` user (public key matching the provided private key);
* a running **Traefik v3** instance using the file provider and watching
  `teknoo.east.paas.docker-compose.traefik.dynamic_dir` — the driver only drops files and connects networks;
  it does not install or manage Traefik (see `documentation/traefik.ingress.md`).

See also
--------

* `documentation/traefik.ingress.md` — Traefik v3 static configuration, entrypoints, ACME, TLS, path
  mapping and the watched-directory contract.
* `documentation/README.md` — overview of all bundled infrastructures.
