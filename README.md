Teknoo Software - PaaS library
==============================

[![Latest Stable Version](https://poser.pugx.org/teknoo/east-paas/v/stable)](https://packagist.org/packages/teknoo/east-paas)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-paas/v/unstable)](https://packagist.org/packages/teknoo/east-paas)
[![Total Downloads](https://poser.pugx.org/teknoo/east-paas/downloads)](https://packagist.org/packages/teknoo/east-paas)
[![License](https://poser.pugx.org/teknoo/east-paas/license)](https://packagist.org/packages/teknoo/east-paas)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

`East PaaS` is an universal package, following the #East programming philosophy, build on
[Teknoo East Foundation](https://github.com/TeknooSoftware/east-foundation) and
[Recipe](https://github.com/TeknooSoftware/recipe) to implement a custom
[PaaS](https://en.wikipedia.org/wiki/Platform_as_a_service) manager like [Platform.sh](https://platform.sh/).

This library is able to fetch a project on a source repository (like `Git`) in a temporary folder, read a deployment
file (by default, called `.paas.yaml`) run some hooks to install vendors (with `composer`, `npm`, `pip`, etc..), compile 
or other  (`make`, `symfony console`), warmup cache, create OCI image (with `buildah` or `docker build`) and deploy the
project them in a cluster (`kubernetes`).

The deployment on `Kubernetes` includes :
- `Namespace`
- `ReplicaSets` or `Deployments` (with `Pods`)
- `ConfigMap` and `Secrets`
- `Service` and `Ingress`

`Teknoo East PaaS` is compatible with `Docker` or any `OCI implementation` (like `BuildAh`) and `Kubernetes`.
An implementation with `Docker Swarm` can be added.

`Teknoo East PaaS` is bundled with a default implementation with `Symfony` 6.4 or newer and `Doctrine ODM MongoDB` to
persist data.

Documentation
------------
The documentation, about the architecture of this library and its behavior is available [here](documentation/README.md)

Example with Symfony
--------------------
An example of integration with `Symfony` file is available [here](documentation/example.symfony.md)

Example of **.paas.yaml** configuration file present into git repository to deploy
---------------------------------------------------------------------------------
An example of the `.paas.yaml` file is available [here](documentation/example.paas.md)

Support this project
---------------------
This project is free and will remain free. It is fully supported by the activities of the EIRL.
If you like it and help me maintain it and evolve it, don't hesitate to support me on
[Patreon](https://patreon.com/teknoo_software) or [Github](https://github.com/sponsors/TeknooSoftware).

Thanks :) Richard.

Credits
-------
EIRL Richard Déloge - <https://deloge.io> - Lead developer.
SASU Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge, as part of EIRL Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
sharing knowledge and skills.

License
-------
Space is licensed under the MIT License - see the licenses folder for details.

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-paas

This library requires :

    * PHP 8.1+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Optional: Symfony 6.3+ (for administration)

Contribute :)
-------------
You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
