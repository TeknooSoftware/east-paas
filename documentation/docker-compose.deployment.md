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
                     Accumulator (in-memory)
                        • compose: services / networks / volumes / configs / secrets
                        • traefik: http / tcp / udp routers + services, tls
                        • files:   map<path, content> (secrets, configs, TLS certs)
                        • network name: string
                        ▼
                     Driver serializes the accumulator to YAML, writes the playbook,
                     a single-host inventory and all pushed files into a per-run temp dir
                        ▼
                     RunnerInterface (SymfonyProcessRunner) ── SSH ─► Docker host
                        deploy.yml : create dirs, push files, `docker compose -p <project> up -d`,
                                     run during-deployment jobs, `docker network connect <net> traefik`
                        expose.yml : push TLS files + `<project>.yml` into Traefik's watched directory
                        ▼
                     promise->success(['compose' => …, 'traefik' => …, 'output' => …])
                        └─► flows into the job History (Deploying:Result / Exposing:Result)
```

Unlike Kubernetes (where each manifest is `apply()`-ed immediately against a live `Client`), the Compose
transcribers only **accumulate** into an `Accumulator` builder. The driver then serializes the accumulator
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
| `getCaCertificate()`        | optional SSH **host public key(s)** of the Docker host, materialized to a `known_hosts` file: the run then enforces a strict host key checking (`StrictHostKeyChecking=yes`). Accepts bare keys (`ssh-ed25519 AAAA…`, bound to the cluster address host, `[host]:port` for a non-default port) or full `known_hosts` lines. When absent, the host key checking is **disabled** (`ANSIBLE_HOST_KEY_CHECKING=False`), Ansible would otherwise prompt and hang the worker. |
| `getPassword()`             | **unused** by this driver (no `become`, the SSH user must be allowed to run `docker` directly).                                |
| `getToken()`                | **unused** by this driver.                                                                                                     |
| `getClientCertificate()`    | **unused** by this driver.                                                                                                     |

The private key (and the optional `known_hosts`) is materialized to a temporary file by `RunnerFactory`
(Ansible refuses world-readable keys, hence `chmod 0600`) and the temp files are removed in the factory's
`__destruct()`, mirroring the write/unlink discipline of the Kubernetes `Factory`. The per-run working
directory (compose file, secret and env files, TLS keys, inventory) is removed by the driver as soon as the
playbook has run, whatever its outcome.

Compose project & network naming
--------------------------------

* **Compose project name** (`docker compose -p <project>`) =
  `sanitizeDns("{namespace}-{projectName}")` where `namespace` is the cluster's `namespace` and
  `projectName` comes from `CompiledDeployment::withJobSettings()`. This isolates both the **project** and
  the **environment** (the cluster already carries the per-environment namespace). `sanitizeDns()`
  lowercases the value and replaces every character outside `[a-z0-9-]` with `-`, collapsing repeats and
  trimming leading/trailing `-`.

* **Dedicated network.** Each project gets a dedicated network declared inside the Compose file with an
  explicit `name:` `"{project}-private"` (so Compose does not prefix it again). It uses the `bridge` driver
  (configurable). By default the containers keep an egress (like Kubernetes pods: external APIs, SMTP,
  package downloads in jobs...); set `teknoo.east.paas.docker-compose.network.internal` to `true` to declare
  it `internal: true` (no egress, containers reachable only through Traefik; note that host ports published
  by a public service are **not** reachable on an internal network). External reachability is provided
  through Traefik (ingresses) and, for public services, through published host ports.

* **Connect-per-project.** The driver exposes the resolved private network name (`getNetworkName()`)
  so the deploy playbook runs `docker network connect <project>-private <traefik-container>`, making Traefik
  join every dedicated project network. The command is idempotent (an "already exists in network" error is
  ignored). Because Traefik is connected to several projects, every generated Traefik resource name is
  prefixed by the project name and the backend URLs use the network-qualified Docker DNS name
  `<pod>.<project>-private`. See `documentation/traefik.ingress.md` for the Traefik side.

What gets generated
-------------------

For each run the driver writes, into a fresh per-run working directory under
`teknoo.east.paas.worker.tmp_dir`:

* `compose.yaml` — the Compose Specification file (`services`, `networks`, `volumes`, `configs`, `secrets`).
* `<project>.yml` — **expose stage only** — the Traefik v3 dynamic-configuration file.
* `deploy.yml` / `expose.yml` — the rendered Ansible playbook for the stage.
* `inventory.ini` — the single-host inventory built from `cluster.address`.
* secret / config / env / TLS cert files referenced by the Compose and Traefik configs (see below).

The directory is deleted once the playbook has run.

The `CompiledDeployment` model is mapped to the Compose / Traefik model by a priority-ordered set of
transcribers:

| Priority | Transcriber             | Stage  | Role                                                                  |
|----------|-------------------------|--------|-----------------------------------------------------------------------|
| 10       | `SecretTranscriber`     | deploy | PaaS `map` `Secret` → one Compose `secrets` entry (and file) per key   |
| 10       | `ConfigMapTranscriber`  | deploy | PaaS `Map` → one Compose `configs` entry (and file) per key           |
| 10       | `VolumeTranscriber`     | deploy | persistent volumes → named `local` volumes                            |
| 30       | `DeploymentTranscriber` | deploy | pods → Compose services (one anchor + sidecars sharing the network)   |
| 32       | `JobTranscriber`        | deploy | during-deployment jobs only (Compose `jobs` profile)                  |
| 35       | `ServiceTranscriber`    | deploy | PaaS services → DNS aliases of the pod; public services → host `ports` |
| 50       | `IngressTranscriber`    | expose | HTTP(S) ingresses → Traefik routers/services + TLS                    |

The dedicated network is declared by the `Accumulator` itself (with the services).

### Pods → services

* A **single-container pod** becomes one Compose service named after the pod (so `Service.podName`
  resolves).
* A **multi-container pod** becomes an **anchor** service (the first container, named after the pod) plus one
  service per sidecar using `network_mode: "service:<anchor>"`, so they share the pod's localhost and port
  space (replicating Kubernetes pod network sharing). Shared pod volumes are mounted on each.
* Container ports map to Compose `expose:` (intra-network only — no host `ports:` for internal services),
  health checks map to Compose `healthcheck` (`curl` for HTTP probes and `nc` for TCP probes must exist in
  the image; a 5s timeout is applied), resource requirements to `deploy.resources.reservations` /
  `deploy.resources.limits` (`cpu` → `cpus` as a decimal, `500m` → `0.5`; `memory` binary suffixes `Mi`/`Gi`
  → `M`/`G`; other resource types have no Compose equivalent and are skipped), replicas to
  `deploy.replicas` (on the anchor only), and the restart policy to `restart`. `fsGroup` is mapped to
  `group_add` (best effort: the volumes are not chowned).

### Services → DNS aliases and host ports

Compose has no `Service` object: a Compose service is reachable on the dedicated network by its own DNS
name (the pod name) on its **container** ports. The `ServiceTranscriber` declares every PaaS service name
as a DNS **alias** of its pod's Compose service, so `http://<service>` resolves inside the stack, but on
the container (target) port: the service's `listen` port is not translated for pod-to-pod traffic (use the
target port, or the same port on both sides).

A public (`internal: false`) service publishes its ports on the host (`ports: <listen>:<target>`, `/udp`
for UDP). A host port can be bound by a single container, so the publication is **skipped, with a
warning** reported in the deploy result (job History), when the pod is replicated: expose the pod through
an ingress instead.

### Secrets, maps and variables

Only `map`-provider secrets (inline values in the `.paas.yaml`) are available: there is no vault on a
plain Docker host, a reference to any other provider fails the deployment.

* A secret / map becomes one Compose `secrets:` / `configs:` entry **per key** (`<name>-secret-<key>`,
  `<name>-map-<key>`), backed by a file pushed under `secrets/<name>-secret/<key>` (mode `0600`) or
  `configs/<name>-map/<key>`. A `base64:` prefixed secret value is decoded.
* A secret / map **volume** is mounted one file per key under its `mount-path` (`<mount-path>/<key>`),
  exactly like Kubernetes.
* Variables read from secrets / maps (`from-secrets`, `import-secrets`, `from-maps`, `import-maps`) are
  resolved by the driver into a per-container **env file** `secrets/<pod>-<container>.env` (mode `0600`,
  values quoted and `$` escaped so Compose does not interpolate them) referenced by `env_file:`. Neither
  the Compose file nor the job History carries a secret value. A reference to a missing key yields an
  empty variable (Kubernetes would refuse to start the container).

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
  `docker compose -p <project> --profile jobs run --rm <svc>`, once per **completion** (sequentially,
  `parallel` is not supported on a single host), wrapped in `timeout <timeLimit>` when a time limit is set,
  and fails the deployment when the exit code is not one of the configured success exit codes (`0` by
  default).

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
2. push `compose.yaml`, secret / env files (mode `0600`, directories `0700`) and config files;
3. when some volumes are flagged `resetOnDeployment`: `docker compose -p <project> down --remove-orphans`
   (a volume in use cannot be removed) then `docker volume rm <project>_<volume>` for each of them;
4. `docker compose -p <project> up -d --remove-orphans`;
5. run during-deployment jobs (`docker compose -p <project> --profile jobs run --rm <svc>`, one run per
   completion, with the time limit and the accepted exit codes);
6. `docker network connect <project>-private <traefik-container>` — idempotent.

**`expose.yml.template`:**

1. push the TLS cert/key files into the Traefik certs directory (mode `0600`);
2. push the `<project>.yml` Traefik dynamic file into Traefik's **watched directory**. Traefik
   (`watch: true`) reloads automatically — no restart.

The single-host **inventory** is generated per run from `cluster.address` (`ansible_host`, `ansible_port`);
the SSH user and private key are applied by the runner from the `ClusterCredentials`. The run is
non-interactive: `ANSIBLE_NOCOLOR=1`, and the host key checking is enforced against the materialized
`known_hosts` (when `getCaCertificate()` is set) or disabled.

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
| `teknoo.east.paas.docker-compose.network.internal`                   | `false`                | declare the dedicated network `internal: true` (no egress) |
| `teknoo.east.paas.docker-compose.traefik.container`                  | `traefik`              | Traefik container name/id to `network connect` |
| `teknoo.east.paas.docker-compose.traefik.dynamic_dir`                | `/etc/traefik/dynamic` | Traefik watched directory                      |
| `teknoo.east.paas.docker-compose.traefik.certs_dir`                  | `/etc/traefik/certs`   | host dir for pushed TLS cert/key files         |
| `teknoo.east.paas.docker-compose.traefik.certs_mount_dir`            | _(= `certs_dir`)_      | the same directory as seen by the Traefik process (path referenced by the dynamic file) |
| `teknoo.east.paas.docker-compose.traefik.default_certresolver`       | _(none)_               | ACME resolver name for `meta.letsencrypt`      |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.web`             | `web`                  | HTTP entrypoint name                           |
| `teknoo.east.paas.docker-compose.traefik.entrypoint.websecure`       | `websecure`            | HTTPS entrypoint name                          |
| `teknoo.east.paas.docker-compose.traefik.default_middlewares`        | `[]`                   | middlewares attached to every generated router |
| `teknoo.east.paas.docker-compose.ingress.default_service.name`       | _(none)_               | default backend service name for ingresses without one |
| `teknoo.east.paas.docker-compose.ingress.default_service.port`       | _(none)_               | default backend service port                   |
| `teknoo.east.paas.docker-compose.https_backend.insecure_skip_verify` | `false`                | skip TLS verification to HTTPS backends (a `serversTransport` is generated) |

See `documentation/traefik.ingress.md` for the Traefik static configuration that must be in place on the
host.

Host prerequisites
------------------

On the **worker** (where the deployment runs):

* `ansible` / `ansible-playbook` available on `PATH` (or pointed to via
  `teknoo.east.paas.docker-compose.ansible.binary`); only `ansible.builtin` modules are used, no collection
  and no Python Docker SDK are required on the Docker host.

The worker invokes `ansible-playbook` through `SymfonyProcessRunner` (the default `RunnerInterface`); the
DI container may inject another implementation behind the same contract.

On the **Docker host** (the `cluster.address` target):

* **Docker Engine + Compose v2** (`docker compose …`, not the legacy `docker-compose`);
* **SSH** access for the mapped `ClusterCredentials` user (public key matching the provided private key),
  allowed to run `docker` (member of the `docker` group), with write access to `deploy_root`, the Traefik
  dynamic and certs directories, and `timeout` (coreutils) for jobs with a time limit;
* the registry credentials: unlike Kubernetes (`imagePullSecrets`), nothing is pushed for the image pull.
  The SSH user must already be logged in on the OCI registry (`docker login`, i.e. a valid
  `~/.docker/config.json`) so `docker compose up` can pull the built images;
* a running **Traefik v3** instance using the file provider and watching
  `teknoo.east.paas.docker-compose.traefik.dynamic_dir`, with the certs directory bind-mounted at the path
  given by `teknoo.east.paas.docker-compose.traefik.certs_mount_dir` — the driver only drops files and
  connects networks; it does not install or manage Traefik (see `documentation/traefik.ingress.md`).

See also
--------

* `documentation/traefik.ingress.md` — Traefik v3 static configuration, entrypoints, ACME, TLS, path
  mapping and the watched-directory contract.
* `documentation/README.md` — overview of all bundled infrastructures.
