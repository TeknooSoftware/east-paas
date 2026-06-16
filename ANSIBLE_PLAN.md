# ANSIBLE_PLAN — Ansible + Docker Compose + Traefik deployment driver

> Implementation plan for a new cluster deployment driver. Execute the phases at the bottom in order.
> No code from this document has been written yet.

---

## Context

`teknoo/east-paas` compiles a `.paas.yaml` into an immutable `CompiledDeployment` domain model, then a
cluster driver implementing `Teknoo\East\Paas\Contracts\Cluster\DriverInterface`
(`src/Contracts/Cluster/DriverInterface.php`) transcribes that model to a target orchestrator. Today the
only driver is Kubernetes (`infrastructures/Kubernetes/`). We add a second, agnostic driver that deploys to
a Docker host over **SSH + Ansible**, expressing the app as a **Compose Specification** file, and exposes
services through **Traefik v3** using its **file provider** (watched directory). Image building is
unchanged — only the **deploy** and **expose** stages are new.

**Fundamental difference vs Kubernetes.** K8S transcribers receive a *live* `Teknoo\Kubernetes\Client` and
`apply()` each manifest immediately, returning the applied object per resource. There is no live client
here: Compose transcribers **accumulate** a single Compose file + Traefik dynamic-config in an in-memory
builder; the driver then **serializes to YAML and runs `ansible-playbook` once per stage** (mirroring how
`infrastructures/Image` runs `buildah`/`docker` via Symfony Process).

## Decisions (locked)

| # | Decision |
|---|----------|
| Q1 | **SSH transport.** Worker runs `ansible-playbook` locally; Ansible connects over SSH to the host at `cluster.address`. The binary is invoked via **raw Symfony Process** behind our own `RunnerInterface` contract (the contract keeps the runner swappable for other implementations). |
| Q2 | **Reuse `Teknoo\East\Paas\Object\ClusterCredentials`** as the identity (mapping defined below). No new domain object. |
| Q3 | **Connect-per-project.** The deploy playbook runs `docker network connect <project-net> <traefik>` so Traefik joins every dedicated project network. |
| Q4 | **Per-ingress TLS.** `tls.secret` set → materialize cert/key files from the PaaS secret and reference them in Traefik. `meta.letsencrypt: true` → ACME `certResolver`. |
| Q5 | **Platform-side scheduling (option c).** Scheduled jobs (`planning: scheduled`) are **not** emitted into Compose. The East PaaS worker re-dispatches them on time via `symfony/scheduler`. K8S keeps using native CronJob. |
| Q6 | Driver registers in `Cluster\Directory` under the type string **`docker-compose`**. |
| Q7 | **Full scope:** persistent volumes, config maps, secrets, health checks, resource limits, and non-HTTP external TCP/UDP services. |

---

## Architecture overview

```
CompiledDeployment ──► DockerCompose\Driver (States: Generator → Running)
                          │  deploy(): run transcribers tagged Generic/Deployment
                          │  expose(): run transcribers tagged Exposing
                          ▼
                       GenerationInterface (accumulator)
                          • compose: array  (services, networks, volumes, configs, secrets)
                          • traefik: array  (http/tcp/udp routers+services, tls)
                          • files:   map<path,content>  (secrets, configs, TLS certs)
                          • networksToWireToTraefik: string[]
                          ▼
                       Driver serializes accumulator → YAML + writes playbook artifacts → RunnerInterface
                          ▼
                       SymfonyProcessRunner (Symfony Process)  ── SSH ──►  Docker host
                          deploy.yml : ensure dir, push files, `docker compose -p <ns> up -d`,
                                       run during-deployment jobs, `docker network connect <net> traefik`
                          expose.yml : push Traefik dynamic file into watched dir (+ cert files)
                          ▼
                       promise->success(array)  ── flows into job History (Deploying:Result / Exposing:Result)
```

The driver is a Teknoo States proxy with `Generator` (DI/mother, refuses to run) and `Running`
(configured/daughter) states, identical in shape to `infrastructures/Kubernetes/Driver.php` + `Driver/`.

---

## New module layout

PSR-4 root `Teknoo\East\Paas\Infrastructures\DockerCompose\` → `infrastructures/DockerCompose/`.

```
infrastructures/DockerCompose/
├── Driver.php                                  # States proxy, implements DriverInterface
├── Driver/
│   ├── Generator.php                           # refuses to run (GeneratorStateException)
│   └── Running.php                             # runTranscriber(), getRunner(), serialize+invoke ansible
├── Contracts/
│   ├── RunnerFactoryInterface.php              # __invoke(...) : RunnerInterface
│   ├── RunnerInterface.php                     # run(playbookPath, inventory, extraVars, creds, promise)
│   ├── GenerationInterface.php                 # the accumulator contract
│   └── Transcriber/
│       ├── TranscriberInterface.php            # transcribe(cd, GenerationInterface, promise, bag, namespace)
│       ├── GenericTranscriberInterface.php     # runs in deploy (e.g. Network)
│       ├── DeploymentInterface.php             # runs in deploy
│       ├── ExposingInterface.php               # runs in expose
│       └── TranscriberCollectionInterface.php
├── Generation.php                              # GenerationInterface impl (mutable builder)
├── RunnerFactory.php                           # builds SymfonyProcessRunner; writes SSH key to tmp (0600)
├── SymfonyProcessRunner.php                    # runs `ansible-playbook` via Symfony Process
├── TranscriberCollection.php                   # priority-ordered, mirrors K8S
├── Transcriber/
│   ├── CommonTrait.php                         # createPrefixer(), name sanitising (DNS-safe), cleanResult()
│   ├── PodsTranscriberTrait.php                # Pod→service(s) shared logic (containers, env, volumes, health)
│   ├── NetworkTranscriber.php                  # GenericTranscriberInterface  (priority 5)
│   ├── SecretTranscriber.php                   # DeploymentInterface          (priority 10)
│   ├── ConfigMapTranscriber.php                # DeploymentInterface          (priority 10)
│   ├── VolumeTranscriber.php                   # DeploymentInterface          (priority 10)
│   ├── DeploymentTranscriber.php               # DeploymentInterface          (priority 30)  pods→services
│   ├── JobTranscriber.php                      # DeploymentInterface          (priority 32)  during-deployment only
│   ├── ServiceTranscriber.php                  # ExposingInterface            (priority 40)
│   └── IngressTranscriber.php                  # ExposingInterface            (priority 50)
├── Exception/
│   ├── GeneratorStateException.php
│   └── InvalidConfigurationException.php
├── templates/
│   ├── deploy.yml.template                     # Ansible playbook (deploy stage)
│   └── expose.yml.template                     # Ansible playbook (expose stage)
└── di.php                                      # PHP-DI; registers driver under 'docker-compose'
```

Tests mirror at `tests/infrastructures/DockerCompose/` (namespace
`Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\`).

---

## Component specifications

### 1. `Driver` + states (`Driver.php`, `Driver/Generator.php`, `Driver/Running.php`)

Copy the structure of `infrastructures/Kubernetes/Driver.php`:

- `#[StateClass(Generator::class)]`, `#[StateClass(Running::class)]`, `ProxyTrait`, `AutomatedTrait`,
  `#[Property(...)]` assertions on `master`/`defaultsBag`/`namespace` to switch states.
- Constructor: `(RunnerFactoryInterface $runnerFactory, TranscriberCollectionInterface $transcribers)`.
- `configure(string $url, ?IdentityInterface $identity, DefaultsBag $bag, string $namespace, bool $useHierarchicalNamespaces)`:
  clone self, store fields. Reject identities that are not `ClusterCredentials` via an
  `UnsupportedIdentityException` (copy from K8S `Driver/Exception/`). **`useHierarchicalNamespaces` is
  ignored** (K8S-only) — store but never use; document it.
- `deploy(cd, promise)` → `runTranscriber(cd, promise, runDeployment: true, runExposing: false)`.
- `expose(cd, promise)` → `runTranscriber(cd, promise, runDeployment: false, runExposing: true)`.

`Driver/Running.php::runTranscriber()` differs from K8S:
1. Build a fresh `Generation` accumulator.
2. Loop `$this->transcribers` in priority order; for each transcriber matching the stage
   (`GenericTranscriberInterface`/`DeploymentInterface` on deploy, `ExposingInterface` on expose), call
   `transcribe($cd, $generation, $perResourcePromise, $this->defaultsBag, $this->namespace)`. Per-resource
   failures break the loop (same reusable-promise pattern as K8S).
3. After the loop, hand the accumulator to a private `applyGeneration()` that:
   - serializes `$generation->getComposeFile()` to `compose.yaml` (Symfony `Yaml::dump`, inline depth high,
     4-space indent),
   - on **expose** serializes `$generation->getTraefikConfig()` to `<project>.yml`,
   - writes the playbook (from the matching template) + an inventory file + all `$generation->getFiles()`
     into a per-run temp dir (from `teknoo.east.paas.worker.tmp_dir`),
   - calls `($this->runnerFactory)(...)` → `RunnerInterface::run(playbook, inventory, extraVars, $this->credentials, $promise)`,
   - `promise->success([...])` with the generated `compose`/`traefik` arrays + Ansible stdout summary
     (sensitive values stripped, like K8S `cleanResult()`), so it lands in job History.
- `Generator::runTranscriber()` throws `GeneratorStateException` (copy K8S).

### 2. Identity — `ClusterCredentials` → SSH mapping

`ClusterCredentials` getters available: `getCaCertificate`, `getClientCertificate`, `getClientKey`,
`getToken`, `getUsername`, `getPassword`. Mapping used by `RunnerFactory`:

| ClusterCredentials | SSH / Ansible use |
|---|---|
| `getUsername()` | `ansible_user` (SSH login). If empty, fall back to the user in `cluster.address`. |
| `getClientKey()` | SSH **private key** → written to a temp file `chmod 0600`; `ansible_ssh_private_key_file`. |
| `getPassword()` | optional SSH password / `--ask-become-pass` value / become password. |
| `getCaCertificate()` | optional host public key for `known_hosts` (strict host-key checking). |
| `getToken()`, `getClientCertificate()` | unused by this driver (documented). |

`cluster.address` formats accepted: `ssh://user@host:port`, `host:port`, `host`. Parse host/port; default
port 22. Document this in `documentation/docker-compose.deployment.md`.

### 3. Ansible execution (`RunnerFactoryInterface`, `RunnerInterface`, `RunnerFactory`, `SymfonyProcessRunner`)

- `RunnerInterface::run(string $playbookPath, string $inventoryPath, array $extraVars, ?ClusterCredentials $credentials, PromiseInterface $promise): RunnerInterface`.
- **`SymfonyProcessRunner`** is the single implementation: it calls the raw `ansible-playbook` binary via
  `Symfony\Component\Process\Process` (command line built from playbook path, `--inventory`, `--extra-vars`
  as JSON, SSH user/key). Behat can substitute a fake `Process`. Resolve success/failure exactly like
  `infrastructures/Image/ImageWrapper/Running.php::waitProcess()`: a successful process →
  `promise->success(output)`, a failed one → `promise->fail(new RuntimeException($stderr))`. Honour a
  configurable timeout (default 300s). `RunnerInterface` is kept as a contract so other runner
  implementations remain pluggable.
- `RunnerFactory` (mirrors K8S `Factory`): constructor `(string $tmpDir, string $playbookBinary='ansible-playbook', ?float $timeout=null, ?callable $tmpNameFunction=null, ?callable $runnerBuilder=null)`; `__invoke()` writes the SSH key from `ClusterCredentials` to a temp file (`chmod 0600`), returns a configured `SymfonyProcessRunner` (or whatever `$runnerBuilder` builds); cleans temp files in `__destruct()` (copy K8S `write()`/`unlink` discipline).

### 4. Accumulator (`GenerationInterface`, `Generation.php`)

Mutable builder collecting everything before serialization:
- `addService(string $name, array $spec)`, `addNetwork`, `addVolume`, `addConfig(name, spec, ?content)`,
  `addSecret(name, spec, ?content)`.
- `addTraefikRouter(string $kind /* http|tcp|udp */, string $name, array $spec)`,
  `addTraefikService(...)`, `addTlsCertificate(certFile, keyFile)`, `setCertResolver(name)`.
- `addFile(string $relativePath, string $content)` (secrets, configs, certs — written by Ansible to host).
- `wireNetworkToTraefik(string $networkName)`.
- `getComposeFile(): array`, `getTraefikConfig(): array`, `getFiles(): array`, `getNetworksToWire(): array`.
- Knows the **compose project name** (the "namespace") and **dedicated network name**.

### 5. Transcriber contract & collection

`TranscriberInterface::transcribe(CompiledDeploymentInterface $cd, GenerationInterface $generation, PromiseInterface $promise, DefaultsBag $defaultsBag, string $namespace): TranscriberInterface`.
Marker interfaces `GenericTranscriberInterface` / `DeploymentInterface` / `ExposingInterface` partition the
two stages exactly like K8S. `TranscriberCollection` is the same priority queue as
`infrastructures/Kubernetes/TranscriberCollection.php` (copy + retype).

### 6. Namespace / project / network naming

- **Compose project** (`docker compose -p <project>`) = `sanitizeDns("{namespace}-{projectName}")`, where
  `namespace` = `cluster.namespace` and `projectName` comes from `CompiledDeployment::withJobSettings()`.
  This isolates project **and** environment (the cluster already carries `environment`/per-env `namespace`),
  matching the dedicated-namespace requirement. `sanitizeDns` lowercases and replaces non `[a-z0-9-]`.
- **Dedicated networks** are declared inside the Compose file (e.g. a network named `private`); Compose
  auto-prefixes them with the project name → real network `"{project}_private"`. Default driver `bridge`,
  `internal: true` for the private network so containers cannot reach the host/outside unless a service
  explicitly requires host publishing. External/HTTP reachability is provided only through Traefik.
- The driver records the resolved private network name in `wireNetworkToTraefik()` so the deploy playbook
  connects Traefik to it.

### 7. Transcribers — mapping rules

#### NetworkTranscriber (generic, prio 5)
Declares the project's dedicated network(s) in `compose.networks` (`{private: {driver: bridge, internal: true}}`)
and calls `wireNetworkToTraefik(<resolved name>)`.

#### SecretTranscriber (deploy, prio 10)
`CompiledDeployment::foreachSecret`. The PaaS `Secret` (`provider`, `options`, `type`) with `provider: map`
carries inline values in `options`. Map to a Compose **`secrets`** entry `{ <prefixed>: { file: ./secrets/<name> } }`
and `addFile("secrets/<name>", <serialized value(s)>)`. Containers referencing the secret get either a
`secrets:` mount or an env var (see DeploymentTranscriber). Secrets referenced by an ingress `tls.secret`
also produce the cert/key files consumed by `IngressTranscriber` (the secret's `options` must contain
`tls.crt`/`tls.key` style keys; document the expected keys).

#### ConfigMapTranscriber (deploy, prio 10)
`foreachMap`. PaaS `Map.options` (key→value) → Compose **`configs`** entries (`{ <prefixed>: { content: <yaml/string> } }`
or `file:`), plus `addFile` when file-backed. Used by `from-map` env and map-volume mounts.

#### VolumeTranscriber (deploy, prio 10)
`foreachVolume`. Only **persistent**, **secret** and **map** volumes matter at deploy (populated/embedded
volumes are already baked into the OCI image during the build stage via `EmbeddedVolumeImage`):
- `PersistentVolumeInterface` → named Compose **`volume`** `{ <prefixed>: { } }` (local driver). `storageSize`
  is advisory and **not enforced** by the local driver — document this limitation; `allowWriteMany` is a
  no-op locally (single host). `resetOnDeployment` → deploy playbook removes the volume before `up`.
- `SecretVolume` → mounted via Compose `secrets:` on the consuming service.
- `MapVolume` → mounted via Compose `configs:` on the consuming service.

#### DeploymentTranscriber + PodsTranscriberTrait (deploy, prio 30)
`foreachPod` (also yields each Job's pods — JobTranscriber filters those out by name/ownership; mirror K8S
filtering). Per `Pod`:
- **Single-container pod** → one Compose service named after the pod (so `Service.podName` resolves).
- **Multi-container pod** (e.g. `nginx`+`waf`+`blackfire`) → first container = **anchor** service named after
  the pod; each sidecar = its own service with **`network_mode: "service:<anchor>"`** so they share the
  pod's localhost + port space (replicates K8S pod network sharing). Volumes shared across the pod are
  mounted on each.
- Container fields → Compose keys:

  | PaaS (`Container`) | Compose service key |
  |---|---|
  | `image` + `version`/registry (resolved `Image::getUrl()`) | `image` |
  | `listen[]` (ports) | `expose: [..]` (intra-network only; **no host `ports:`** for internal services) |
  | `variables` (plain) | `environment` |
  | `variables` referencing `SecretReference` | `secrets:` mount or `environment` from secret file |
  | `variables` referencing `MapReference` | `environment` / `configs:` |
  | `volumes` (persistent/secret/map) | `volumes:` / `secrets:` / `configs:` with `getVolumeMountPath()` |
  | `HealthCheck` | `healthcheck` (see table below) |
  | `ResourceSet` (cpu/memory require+limit) | `deploy.resources.reservations` + `deploy.resources.limits` |
  | Pod `replicas` | `deploy.replicas` (omit `container_name`; no host port publishing when >1) |
  | Pod `RestartPolicy` (always/on-failure/never) | `restart: always` / `on-failure` / `no` |
  | Pod `fsGroup` | `user`/`group_add` best-effort (document partial support) |
  | all services | `networks: [private]` |

- **HealthCheck mapping** (`HealthCheckType`):
  - `Command` → `healthcheck.test: ["CMD", ...command]`
  - `Tcp` → `healthcheck.test: ["CMD-SHELL", "nc -z localhost <port> || exit 1"]` (documented shell form)
  - `Http` → `healthcheck.test: ["CMD-SHELL", "curl -fk http(s)://localhost:<port><path> || exit 1"]`
    (`isSecure` chooses `https`)
  - `initialDelay`→`start_period`, `period`→`interval`, `failureThreshold`→`retries`.

#### JobTranscriber (deploy, prio 32) — during-deployment only
`foreachJob`, **skip `Planning::Scheduled`** (handled platform-side). For `Planning::DuringDeployment`:
emit the job's pod(s) as Compose services under a Compose **profile** `jobs` (so they are not started by a
plain `up`), `restart: "no"`. The deploy playbook runs them with `docker compose -p <ns> run --rm <svc>`
honouring `isParallel`, `completionsCount`, and `SuccessCondition.successExitCode`/`failureExitCode`
(playbook checks exit codes). `timeLimit` → playbook `async`/`poll` timeout.

#### ServiceTranscriber (expose, prio 40)
`foreachService`. `Service{name, podName, ports: [listen=>target], protocol(TCP|UDP|HTTPS), internal}`:
- `internal: true` → nothing host-facing; service is already reachable on `private` by its Compose DNS name.
  Optionally add a network alias = service name.
- `internal: false` (external):
  - `protocol` HTTP/HTTPS → reachable by Traefik through the wired network; actual host exposure comes from
    an `Ingress`. If an external HTTP(S) service has **no** ingress, emit a Traefik HTTP router/service so it
    is still reachable (host rule from service name is not possible — document that bare external HTTP
    services without an ingress are reachable only internally unless an ingress names them).
  - `protocol` raw TCP/UDP → emit a **Traefik TCP/UDP router+service** (`tcp.routers`/`udp.routers`,
    `HostSNI(*)` for TCP) bound to a dedicated entrypoint, **OR** (the "service requires it" case) publish a
    host port via Compose `ports:` on the service. Default to Traefik TCP/UDP entrypoints; allow host-port
    publishing via service metadata. Document both and the required Traefik static entrypoints.

#### IngressTranscriber (expose, prio 50)
`foreachIngress`. `Ingress{name, host, aliases[], provider, defaultServiceName/Port, paths[](path,serviceName,servicePort), tlsSecret, httpsBackend, meta}`
→ Traefik **dynamic file** (`http`):
- One **router** per ingress: `rule: "Host(\`host\`) || Host(\`alias\`)..."`; `entryPoints: [websecure]` when
  TLS else `[web]`; `service: <ingress>-default`.
- **Path mapping**: each `IngressPath` → an extra router `rule: "Host(...) && PathPrefix(\`/php\`)"` →
  `service: <prefixed serviceName>` (Traefik priority orders longest path first).
- **Services**: `http.services.<name>.loadBalancer.servers[].url = "http(s)://<compose-service-dns>:<targetPort>"`.
  `httpsBackend: true` → scheme `https` + `serversTransport` with `insecureSkipVerify` (configurable).
- **TLS (Q4)**:
  - `tlsSecret` present → `IngressTranscriber` asks `Generation` for the cert/key files produced from that
    secret (`addTlsCertificate(certFile, keyFile)`); router `tls: {}`.
  - `meta.letsencrypt: true` → router `tls: { certResolver: <configured resolver>, domains: [{main: host, sans: [...aliases]}] }`.
  - both absent → plain `web` entrypoint (HTTP only).
- `meta.annotations` → optional Traefik middlewares mapping (configurable mapper, mirroring K8S
  `backendProtocolAnnotationMapper`).
- DI defaults parallel K8S ingress DI: default entrypoints, default certResolver, default middlewares,
  default service/port.

### 8. Ansible playbook templates

`templates/deploy.yml.template` (rendered with project name, paths; `{% %}` placeholders like the Image
builder templates):
1. `file: state=directory` for `/<deploy_root>/<project>` on the host.
2. `copy`/`template` the `compose.yaml`, secret files, config files (mode 0600 for secrets).
3. Optional `community.docker.docker_volume: state=absent` for `resetOnDeployment` volumes.
4. `community.docker.docker_compose_v2: project_src=... state=present` **or** `command: docker compose -p <project> up -d --remove-orphans`.
5. Run during-deployment jobs: `docker compose -p <project> --profile jobs run --rm <svc>` (loop, check rc).
6. `docker network connect <project>_private <traefik_container>` — **idempotent** (ignore "already in
   network"); the Traefik container name/id is a DI/host setting.

`templates/expose.yml.template`:
1. `copy` cert/key files into the TLS dir.
2. `template`/`copy` the `<project>.yml` Traefik dynamic file into the **watched directory** (a host path,
   DI/host setting). Traefik `watch: true` reloads automatically — no Traefik restart.

Inventory: a single-host inventory generated per run from `cluster.address` + the SSH mapping
(`ansible_host`, `ansible_port`, `ansible_user`, `ansible_ssh_private_key_file`).

### 9. DI (`infrastructures/DockerCompose/di.php`)

Mirror `infrastructures/Kubernetes/di.php`:
- `RunnerFactoryInterface` factory reading `teknoo.east.paas.worker.tmp_dir`,
  `teknoo.east.paas.docker-compose.ansible.binary` (default `ansible-playbook`),
  `teknoo.east.paas.docker-compose.timeout`.
- Each transcriber via `Class::class . ':class'` override pattern + `is_a()` guard (copy K8S).
- Exposing/host settings:
  `teknoo.east.paas.docker-compose.traefik.dynamic_dir`,
  `...traefik.certs_dir`,
  `...traefik.container` (name/id to `network connect`),
  `...traefik.default_certresolver`,
  `...traefik.entrypoint.web` / `.websecure` / `.tcp` / `.udp`,
  `...deploy_root`,
  `...network.driver` (default `bridge`),
  `...https_backend.insecure_skip_verify` (default false).
- `TranscriberCollectionInterface` priority list (5/10/10/10/30/32/40/50).
- `Driver` via `create()->constructor(get(RunnerFactoryInterface), get(TranscriberCollectionInterface))`.
- `Directory::class => decorate(fn → register('docker-compose', $driver))`.

---

## Platform-side scheduling (Q5c) — separate, clearly-scoped phase

Scheduled jobs are **not** in the Compose file. The worker re-runs them on schedule. K8S is unaffected
(native CronJob). Design:

1. **Deps:** add `symfony/scheduler` (and transitive `dragonmantank/cron-expression`).
2. **Agnostic contract** `src/Contracts/Scheduling/JobSchedulerInterface.php` with
   `register(string $projectId, string $environment, string $jobName, string $cronExpression): void`
   and `forEachDue(\DateTimeInterface $now, callable $callback): void` — keeps `src/` free of Symfony.
3. **Registration step.** New recipe step `src/Recipe/Step/Worker/RegisterScheduledJobs.php` added to
   `RunJob` **after `Exposing`**, which iterates `CompiledDeployment::foreachJob`, and for each
   `Planning::Scheduled` job calls `JobSchedulerInterface::register(...)`. Gated so it only acts for
   clusters whose driver lacks native cron (config flag / driver capability), to avoid double-scheduling on
   K8S.
4. **Symfony implementation** in `infrastructures/Symfony/Components/Scheduling/` persisting schedules
   (Doctrine) + a `#[AsSchedule]`/`ScheduleProviderInterface` that emits a recurring `CheckScheduledJobs`
   message (every minute). Its handler evaluates cron expressions vs now and, for each due job, re-dispatches
   a **scoped** job run.
5. **Scoped run.** Extend the existing `MessageJob` flow with an optional `scheduledJobName` (new stamp /
   nullable field). When set, `RunJob` runs clone→compile→(build if needed)→ executes **only** that job
   through the driver. For the Compose driver, "run a job" = an Ansible step
   `docker compose -p <project> --profile jobs run --rm <job-svc>` against the **already-deployed** stack
   (no full redeploy). This requires a small `DriverInterface` capability or a dedicated method — **open
   design point flagged below**.

> **Open design point (confirm before building phase 8 step 5):** running a single scheduled job without a
> full redeploy needs either (a) a new optional method on the driver (e.g. `runJob(name, cd, promise)`), or
> (b) reuse `deploy()` with a `CompiledDeployment` filtered to that one job. Recommendation: (b) — keep
> `DriverInterface` unchanged; build a filtered `CompiledDeployment` for the scheduled run. The scheduler
> phase can ship after the core driver.

---

## Documentation deliverables

- `documentation/docker-compose.deployment.md` — how the driver works; cluster config (`type: docker-compose`,
  `address: ssh://…`, `ClusterCredentials`→SSH mapping table); compose project/network naming; what gets
  generated; persistent-volume size limitation; during-deployment vs scheduled jobs; required host
  prerequisites (Docker Engine + Compose v2, SSH access, `ansible` on the worker).
- `documentation/traefik.ingress.md` — **how to configure Traefik v3 as the ingress**: static config with
  the **file provider** (`providers.file.directory` + `watch: true`), entrypoints (`web` :80, `websecure`
  :443, optional TCP/UDP entrypoints), ACME `certificatesResolvers` for `meta.letsencrypt`, the
  **connect-per-project** network model (driver runs `docker network connect`), the watched-directory
  contract (one `<project>.yml` per project/env), TLS via provided cert files vs ACME, path mapping and
  TLS examples. **Implementer must verify exact keys against the current Compose Specification
  (docs.docker.com / compose-spec repo) and Traefik v3 docs (doc.traefik.io) at build time** — per the
  instruction to read the official docs.
- Update `documentation/README.md` to list the new infrastructure module.

## Tests deliverables

**Unit** (`tests/infrastructures/DockerCompose/`, PHPUnit attributes `#[CoversClass]`, mirror K8S tests):
- `DriverTest` — Generator refuses; `configure()` rejects non-`ClusterCredentials`; `deploy()`/`expose()`
  iterate the right transcribers and invoke the runner (mock `RunnerFactoryInterface`/`RunnerInterface`).
- `RunnerFactoryTest`, `SymfonyProcessRunnerTest` — mock `Process`; assert
  command/inventory/extraVars/timeout and success-vs-fail promise resolution.
- `GenerationTest` — accumulator add/serialize; Compose + Traefik array shape.
- One test per transcriber — feed a mock `CompiledDeploymentInterface` whose `foreach*` invokes the callback
  with hand-built domain objects (`Pod`, `Container`, `Service`, `Ingress`, volumes, `Job`); assert the
  resulting Compose/Traefik arrays (golden arrays).
- `TranscriberCollectionTest`, `ContainerTest` (build container from `di.php`, assert `Driver` and
  `Directory` registration of `'docker-compose'`).

**Behat** (auto-discovered; mirror the in-kubernetes trio):
- `features/Job.start.in-docker-compose.feature` (+ `.with-defaults`, `.with-prefix-or-extends`).
- In `tests/behat/FeatureContext.php`: add `#[Given('a docker-compose orchestrator')]` setting
  `$this->clusterType = 'docker-compose'` and substituting a **fake `RunnerInterface`** (and/or
  `ProcessFactory`) that captures the generated `compose.yaml` + Traefik file + playbook in memory instead
  of running anything. Add `#[Then('some docker compose configuration has been created')]` /
  `#[Then('some traefik configuration has been created')]` asserting the captured artifacts (golden
  strings), following the existing `$this->manifests` capture pattern. Set the test cluster's
  `address`/`ClusterCredentials` for SSH.
- Reuse existing `expectedCD.php` (the compiled model is orchestrator-agnostic). Add golden
  Compose/Traefik fixtures under `tests/behat/` if needed.

## composer.json / autoload / deps changes

- `autoload.psr-4`: add `"Teknoo\\East\\Paas\\Infrastructures\\DockerCompose\\": "infrastructures/DockerCompose/"`.
- `autoload-dev.psr-4`: add `"Teknoo\\Tests\\East\\Paas\\Infrastructures\\DockerCompose\\": "tests/infrastructures/DockerCompose/"`.
- `require-dev`: add `symfony/scheduler` (`symfony/yaml`, `symfony/process`, `symfony/messenger` already
  present) — same convention as `teknoo/kubernetes-client` being dev-only.
- `suggest`: add `symfony/scheduler` ("platform-side scheduling of scheduled jobs").
- No changes needed to `phpunit.xml` / `behat.dist.yml` / `Makefile` — directories are auto-discovered.

## QA & verification

Run from repo root (existing tooling):
1. `php -l` on all new files (Makefile `lint` covers `infrastructures/`).
2. `vendor/bin/phpcs --standard=PSR12 --extensions=php infrastructures/DockerCompose src` → 0 errors.
3. `vendor/bin/phpstan analyse` (level per `phpstan.neon`) → 0 errors.
4. `vendor/bin/phpunit -c phpunit.xml` → new `DockerCompose` unit tests pass; coverage parallels K8S.
5. `vendor/bin/behat` → `Job.start.in-docker-compose*` features green (no real Docker/Ansible — faked).
6. **Manual smoke (optional, real host):** a Docker host reachable over SSH + a running Traefik with file
   provider watching a dir; deploy the demo project; confirm `docker compose -p <ns> ps`, the network
   connect, the `<project>.yml` appears in the watched dir, and the host resolves over Traefik with TLS.
7. `composer validate`.

---

## Phased execution checklist (ordered)

1. **Scaffold module + DI:** module dirs, `Driver` + `Generator`/`Running`, exceptions, empty
   `TranscriberCollection`, `di.php` registering `'docker-compose'`; `ContainerTest` green. Add autoload +
   deps; `composer dump-autoload`.
2. **Ansible execution:** `RunnerInterface`/`RunnerFactoryInterface`, `SymfonyProcessRunner`,
   `RunnerFactory` (SSH key temp file); unit tests.
3. **Accumulator + contracts:** `GenerationInterface`/`Generation`, transcriber interfaces, `CommonTrait`,
   `PodsTranscriberTrait`; unit tests.
4. **Deploy transcribers:** Network, Secret, ConfigMap, Volume, Deployment(+pods), Job(during-deployment);
   wire `deploy()` end-to-end with `deploy.yml.template`; unit tests + golden arrays.
5. **Expose transcribers:** Service, Ingress (incl. TLS Q4, path mapping, aliases, TCP/UDP); wire `expose()`
   with `expose.yml.template`; unit tests.
6. **Behat:** features + `FeatureContext` steps + fake runner capture; green.
7. **Documentation:** `docker-compose.deployment.md`, `traefik.ingress.md`, README update (verify Compose +
   Traefik v3 keys against official docs while writing).
8. **Platform scheduling (Q5c):** `JobSchedulerInterface`, `RegisterScheduledJobs` step, Symfony scheduler
   impl + `CheckScheduledJobs` handler + scoped-run mechanism. **Confirm the open design point first.**
9. **Full QA pass** (phpcs/phpstan/phpunit/behat/composer validate).

## Assumptions / open points

- A Traefik v3 instance already runs on the host with the file provider watching a directory; the driver
  only drops files and connects networks (it does not install/manage Traefik). Container name + watched dir
  + certs dir are host/DI settings.
- Multi-container pods use `network_mode: "service:<anchor>"`; verify this satisfies the demo's
  nginx/waf/blackfire pod at integration time.
- Persistent-volume `storageSize`/`allowWriteMany` are advisory on the local driver (single host).
- The scoped scheduled-run mechanism (phase 8) has an open design choice — recommended: filter the
  `CompiledDeployment` to the single job and reuse `deploy()`, leaving `DriverInterface` unchanged.
