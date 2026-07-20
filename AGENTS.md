# AGENTS.md

## Overview
`east-paas` is a universal Platform-as-a-Service (PaaS) manager library. It provides capabilities to orchestrate application deployment lifecycle, including:
- Fetching source code from repositories (e.g., Git).
- Executing deployment "recipes" (installing dependencies, compiling, warming up cache).
- Building OCI-compliant images (using Buildah or Docker).
- Deploying to orchestration environments like Kubernetes or Docker hosts (via Ansible/Traefik).

## Technology Stack
- **Language:** PHP 8.2+
- **Core Dependencies:** 
    - `teknoo/immutable`
    - `teknoo/states`
    - `teknoo/recipe`
    - `teknoo/east-foundation`
- **Default Implementation:** 
    - Symfony 6.4+
    - Doctrine ODM (MongoDB)
- **Orchestration Support:**
    - Kubernetes (Namespaces, Deployments, ConfigMaps, Secrets, Services, Ingress, Quotas).
    - Docker (via Docker Compose, Ansible, and Traefik v3).
    - OCI (Buildah, Docker).

## Project Structure
- `src/`: Contains the core library logic and domain models.
- `infrastructures/`: Contains infrastructure-specific implementations (e.g., Doctrine ODM configuration, Flysystem drivers).
- `tests/`: Unit and integration tests.
- `features/`: Behat behavior-driven development (BDD) feature files.
- `documentation/`: Detailed architectural and usage documentation.

## Development & Validation
All primary tasks for testing, linting, and validation are managed via `Makefile`.

### Quality Assurance (QA)
- `make lint`: Performs syntax checks on PHP files in `src/` and `infrastructures/`.
- `make phpstan`: Runs static analysis using PHPStan.
- `make phpcs`: Ensures code adheres to PSR12 standards using PHPCS.
- `make audit`: Performs a security audit of Composer dependencies.
- `make qa`: Runs all QA checks (`lint`, `phpstan`, `phpcs`, `audit`).

### Testing
- `make test`: Runs the full test suite:
    - **PHPUnit**: Unit and integration tests.
    - **Behat**: BDD feature tests (runs in parallel by default).
- `make test-mono-thread`: Runs the full test suite in a single thread (useful for debugging).

## Deployment Workflow
The library reads a deployment configuration file (default: `.paas.yaml`) from the target repository to determine the steps required for a successful deployment.
