# ANSIBLE_PLAN_CHECK — application audit of ANSIBLE_PLAN.md

Branch: `feature/ansible-docker-compose`. Audit date: 2026-06-16.

## Verdict

Core driver (plan phases 1–7) and final QA wiring (phase 9) **fully applied**. Module layout,
DI, transcribers, runner, accumulator, templates, unit + behat tests, composer wiring and
documentation all present and matching the plan. **Phase 8 (platform-side scheduling, Q5c) is
NOT implemented** — a deliberate deferral consistent with the plan's own "can ship after the
core driver / confirm open design point first" note. Remaining divergences are minor and benign.

Git log confirms staged delivery: F3 accumulator → F4 deploy → F5 expose → F6 behat →
F7 docs → F9 compliance+qa. No F8 commit.

## Applied as planned ✅

- **Module layout** — all 30 planned files under `infrastructures/DockerCompose/` exist
  (Driver + Generator/Running states, 5 Contracts + 5 Transcriber contracts, Generation,
  RunnerFactory, SymfonyProcessRunner, TranscriberCollection, 8 transcribers + CommonTrait +
  PodsTranscriberTrait, 2 exceptions, deploy/expose templates, di.php).
- **Driver** (`Driver.php`) — States proxy, `DriverInterface` + `AutomatedInterface`,
  `#[StateClass]`/`#[Property]` assertions on master/defaultsBag/namespace. `configure()`
  rejects non-`ClusterCredentials` via `UnsupportedIdentityException`; `useHierarchicalNamespaces`
  accepted-but-ignored with documented rationale (matches plan §1).
- **DI** (`di.php`) — transcriber priorities **exactly** 5/10/10/10/30/32/40/50; `:class`
  override + `is_a()` guard per transcriber; registers driver under type **`docker-compose`**
  via `Directory` decorate. All planned config keys present: `worker.tmp_dir`,
  `docker-compose.ansible.binary`, `.timeout`, `.network.driver`, `.deploy_root`,
  `.traefik.container/.dynamic_dir/.certs_dir/.default_certresolver`,
  `.traefik.entrypoint.web/.websecure/.tcp/.udp`, `.https_backend.insecure_skip_verify`.
- **JobTranscriber** — skips `Planning::Scheduled`, emits only `Planning::DuringDeployment`
  (plan §7 / Q5).
- **Tests** — every planned unit test present (DriverTest, RunnerFactoryTest,
  SymfonyProcessRunnerTest, GenerationTest, TranscriberCollectionTest, ContainerTest, + one per
  transcriber). All 3 behat features (`Job.start.in-docker-compose[.with-defaults|.with-prefix-or-extends]`)
  present. `FeatureContext` has `#[Given('a docker-compose orchestrator')]`, fake
  `RunnerInterface` capture, and both `#[Then(... docker compose / traefik configuration ...)]`.
- **composer.json** — autoload + autoload-dev PSR-4 entries, `require-dev: symfony/scheduler`,
  and `suggest` entry all present.
- **Documentation** — `documentation/docker-compose.deployment.md`,
  `documentation/traefik.ingress.md` created; `documentation/README.md` updated to list the
  new module.

## Divergences ⚠️

| # | Plan item | Status | Notes |
|---|-----------|--------|-------|
| D1 | **Phase 8 — Platform-side scheduling (Q5c)** | **MISSING** | None of `src/Contracts/Scheduling/JobSchedulerInterface.php`, `src/Recipe/Step/Worker/RegisterScheduledJobs.php`, `infrastructures/Symfony/Components/Scheduling/`, a `CheckScheduledJobs` message/handler, or a `scheduledJobName` stamp/field exist. `symfony/scheduler` dep + `suggest` were added in anticipation but no code uses them. **Intentional** per plan (open design point, ships after core). Scheduled jobs are correctly *excluded* from Compose by JobTranscriber, but the re-dispatch mechanism that was to run them does not yet exist. |
| D2 | Driver constructor signature (plan §1: `(RunnerFactoryInterface, TranscriberCollectionInterface)`) | Expanded | Actual ctor also takes `templates`, `tmpDir`, `deployRoot`, `traefikContainer`, `traefikDynamicDir`, `traefikCertsDir`, `tmpDirFactory`. The plan had placed these on `Running`/`di.php`; moving them to the ctor (wired in di.php) is a reasonable structural choice, behavior unchanged. |
| D3 | Behat golden Compose/Traefik fixtures (plan: "add … if needed") | Substituted | No golden YAML files under `tests/behat/`; artifacts are captured in-memory and asserted structurally in the `#[Then]` steps. Allowed by the plan's "if needed". |
| D4 | DI ingress defaults | Extra keys | `di.php` adds `docker-compose.ingress.default_service.name/.port` and `traefik.default_middlewares` beyond the explicitly enumerated keys — consistent with plan §7 ("DI defaults parallel K8S ingress DI"). |
| D5 | Extra exception class | Added | `Exception/BadTempFileException.php` not in plan (temp-file failure path in RunnerFactory). Benign hardening. (`Driver/Exception/UnsupportedIdentityException.php` was specified by plan §1 "copy from K8S" — not a divergence.) |

## QA note

Plan §QA steps are wired (Makefile/phpunit/behat auto-discovery, composer entries). The F9
commit message claims "compliance + qa + test green"; this audit did not re-run phpcs/phpstan/
phpunit/behat — verify by running them if a green confirmation is required.

## Recommendation

Treat D1 (phase 8) as the only substantive gap. Decide whether to (a) implement the scheduler
phase per plan §8 (confirming the open design point — recommended option (b): filter
`CompiledDeployment` to one job and reuse `deploy()`), or (b) formally mark phase 8 out of scope
and remove the now-unused `symfony/scheduler` dep + suggest. D2–D5 need no action.
