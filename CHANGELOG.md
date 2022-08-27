# Teknoo Software - PaaS - Change Log

## [0.0.103] - 2022-08-27
### Dev Release
- Support East Common 1.4.1

## [0.0.102] - 2022-08-20
### Dev Release
- Add new entry in paas configuration :
  * `.defaults.storage-provider`
  * `.defaults.storage-size`
  * `.defaults.oci-registry-config-name`
  To allow developer to define defaults values when they are not defined into pods or volumes.
- Add `IdentityWithConfigNameInterface` to create identity object with a config name to reference it into anothers
  ressources, like for oci registries.

## [0.0.101] - 2022-08-20
### Dev Release
- Fix kubernetes resource name
- Create persistents volumes in kubernetes before use it into pods
- Persistent volume can be reseted (deleted and recreated) at each deployment by setting the option 
 `reset-on-deployment` at true in the paas yaml file
- Improve Buildkit output catching
- Fixing issue of pod with a private OCI registry / docker hub, and secret passed into `imagePullSecrets`
- Allow developpers to custom the secret name to use into pod by adding the option `oci-registry-config-name` in pod
  definition in the paas yaml file.
- Allow vendors to define a default `oci-registry-config-name` value thanks to DI parameter
 `teknoo.east.paas.default_oci_registry_config_name`

## [0.0.100] - 2022-08-17
### Dev Release
- Add support of storage-size for persistent volume
- Persistent volume are converted as PVC into kubernetes
- Add Volume Transcriber for Kubernetes
- Fix bad behavior with namespace transcriber, it must not create namespace when hierarchical NS are not supported

## [0.0.99] - 2022-08-15
### Dev Release
- Add `Account::$prefixNamespace` property to help to manage shared clusters

## [0.0.98] - 2022-08-14
### Dev Release
- Support last version of `Teknoo East Common`
- Update writers to support `prefereRealDateOnUpdate` behavior

## [0.0.97] - 2022-08-06
### Dev Release
- Fix composer.job

## [0.0.96] - 2022-08-01
### Dev Release
- Add `GenericTranscriberInterface` for Kubernetes transcriber to always run
- Fix job list, sort by last updated as first
- By default, the PrepareJob test claim an updated date instance
- Add 'teknoo.east.paas.symfony.prepare-job.prefer-real-date' to allow to disable this behavior

## [0.0.95] - 2022-07-26
### Dev Release
- Add `CompiledDeploymentInterface::forNamespace` to perform some operation with the deployment`s namespace
- Add transcriber to create namespace before other kubernetes transcriber
- Fix some bug on secret transcriber and namespace management
- fix service transcriber

## [0.0.94] - 2022-07-17
### Dev Release
- Fix Cascade in mapping

## [0.0.93] - 2022-07-16
### Dev Release
- Fix Cascade in mapping
 
## [0.0.92] - 2022-07-15
### Dev Release
- Fix Cascade in mapping

## [0.0.91] - 2022-07-10
### Dev Release
- Add `AccountAwareInterface` to extract account's data for external operations

## [0.0.90] - 2022-06-19
### Dev Release
- Fix SaveJob error handler

## [0.0.89] - 2022-06-18
### Dev Release
- Define exception code

## [0.0.88] - 2022-06-17
### Dev Release
- Clean code and test thanks to Rector
- Update libs requirements

## [0.0.87] - 2022-06-06
### Dev Release
- `History::clone` is able to sort new history in correct time order to avoid a later update erase the final update

## [0.0.86] - 2022-06-06
### Dev Release
- Remove Simplify/git-wrapper
- Switch to Symfony Process + direct call git CLI tools (To manage easily ssh keys per project via env vars, 
  without additional tools, gitlib is not usable with dynamic privates keys)
- Update `Infrastructure\Git\CloningAgent` and `Infrastructure\Git\Hook` with `Symfony Process`
- Rename `DispatchJob` message to `MessageJob`

## [0.0.85] - 2022-05-28
### Dev Release
- Improve errors handling in Recipe's steps.

## [0.0.84] - 2022-04-25
### Dev Release
- Configuration file in project repository is not mandatory `.paas.yaml` and msut be
  defined in the DI under the key `teknoo.east.paas.project_configuration_filename`.

## [0.0.83] - 2022-04-21
### Dev Release
- Cookbooks `NewAcccountEndPoint`, `NewProjectEndPoint` and `AbstractEditObjectEndPoint` accepts a new argument in constructorm called `defaultErrorTemplate` to set in the
  initial workplan the `errorTemplate` ingredient to avoid to set for each use.
- This variable can be set in the DI via the special key `teknoo.east.common.cookbook.default_error_template`
- `AbstractEditObjectEndPoint` accepts `$loadObjectWiths` in constructor to define mapping for `LoadObject` step.
- `NewAcccountEndPoint` accepts `$createObjectWiths` in constructor to define mapping for `CreateObject` step.
- `NewProjectEndPoint` accepts `$createObjectWiths` in constructor to define mapping for `CreateObject` step.

## [0.0.82] - 2022-04-19
### Dev Release
- Fix `NewProjectEndPoint` to implement the good interface
- Require `East Common` `1.0.4`
- Accounts and projects endpoints does not require slugs

## [0.0.81] - 2022-04-17
### Dev Release
- Rename `.yml` files to `.yaml`
- Add Account::namespaceIsItDefined

## [0.0.80] - 2022-04-10
### Dev Release
- Fix `routing_admin_account.yml`
- Fix `routing_admin_job.yml`
- Fix `routing_admin_project.yml`

## [0.0.79] - 2022-04-08
### Dev Release
- Fix return type in `JobUnitDenormalizer`
- Fix `routing_admin_account.yml`

## [0.0.78] - 2022-04-08
### Dev Release
- Switch from `East Website` to `East Common`

## [0.0.77] - 2022-03-18
### Dev Release
- Remove `teknoo.east.paas.kubernetes.ssl.verify` and replace by `teknoo.east.paas.symfony.http.client`
- Kubernetes HTTP Client can be injected via the DI key `teknoo.east.paas.symfony.http.client`, else
  HttpPlug will be automatically detect and load the first client found
- `teknoo.east.paas.kubernetes.http.client` is by default created in the DI definitions provided with Symfony integration
  if `HttplugClient` is available.

## [0.0.76] - 2022-03-13
### Dev Release
- Rollback on some imutable object, readonly is not compliant with doctrine dbal/mongodb

## [0.0.75] - 2022-03-11
### Dev Release
- Require Recipe 4.1.2+ or later
- Improve PHPStan analyse

## [0.0.74] - 2022-03-08
### Dev Release
- Support States 6.0.1+
- Support Recipe 4.1.1+
- Support East Foundation 6.0.2+
- Support East Website 7.0.2+
- Remove support of `PHP 8.0`, support only `PHP 8.1+`
- Remove support of `Symfony 5.4`, support only `Symfony 6.0+`
- Public constant are finals
- File's Visibility are Enums
- Service's protocol Enums
- Use readonly properties behaviors on Immutables
- Use `(...)` notation instead array notation for callable
- Enable fiber support in api endpoint

## [0.0.73] - 2022-02-28
### Dev Release
- Support East Foundation 6.0
- Support Recipe 4.0.1

## [0.0.72] - 2022-02-11
### Dev Release
- Support Immutable 3.0
- Support State 6.0
- Support Recipe 4.0

## [0.0.71] - 2021-12-19
### Dev Release
- Fix some deprecation with PHP 8.1

## [0.0.70] - 2021-12-12
### Dev Release
- Remove unused QA tool
- Remove support of Symfony 5.3
- Support Symfony 5.4 and 6.0+
- 
## [0.0.69] - 2021-11-21
### Dev Release
- Switch to PHPStan 1.1+
- Fix some QA

## [0.0.68] - 2021-10-01
### Dev Release
- Add `verifyAccessToUser` to `Account` to check user's rights about a project

## [0.0.67] - 2021-09-13
### Dev Release
- Migrate to last Teknoo libs, Recipe 3.3 and Website 6.0
- Fixing Contracts namespaces about compilation
- QA, Fix PHPDoc

## [0.0.66] - 2021-08-15
### Dev Release
- Improve promise uses

## [0.0.65] - 2021-08-12
### Dev Release
- Switch to `Recipe Promise`
- Remove support of Symfony 5.2

## [0.0.64] - 2021-07-24
### Dev Release
- Fix ReplicationController names in Kubernetes

## [0.0.63] - 2021-07-21
### Dev Release
- Migrate to FlySystem 2.2+

## [0.0.62] - 2021-07-20
### Dev Release
- Improve errors messages, to keep all errors messages.

## [0.0.61] - 2021-07-19
### Dev Release
- History event use by defaut real date instead of first fetched date value

## [0.0.60] - 2021-07-18
### Dev Release
- Fix errors management when job running

## [0.0.59] - 2021-07-12
### Dev Release
- Update documents and dev libs requirements.
- Complete tests.  
- Rename Namespace `Conductor` to `Compilation`.
- Rename Namespace `Conductor\Compilation` to `Compilation\Compiler`.
- Rename Namespace `Compilation` to `Compilation\CompiledDeployment`.

## [0.0.58] - 2021-07-05
### Dev Release
- Update libs requirement

## [0.0.57] - 2021-06-20
### Dev Release
- Update to East Foundation 5.3.1 and Website 5.1.1
- Rework error notifications by adding new ErrorResponse, compliant with PSR and East response to pass to the client
- Add History and Job responses, compliant also with PSR and East for client to be use in any context.

## [0.0.56] - 2021-06-20
### Dev Release
- Update to Recipe 3.1, East Foundation 5.2 and Website 5.1

## [0.0.55] - 2021-06-13
### Dev Release
- Remove Symfony routes about worker and api (use Messenger instead). Endpoints stay available, routes can be implemented
  manually.

## [0.0.54] - 2021-06-13
### Dev Release
- Rework symfony's routing files to allow dev to override easily a some definitions

## [0.0.53] - 2021-05-31
### Dev Release
- Minor version about libs requirements

## [0.0.52] - 2021-04-28
### Dev Release
- Some optimisations on array functions to limit O(n)

## [0.0.51] - 2021-04-11
### Dev Release
- Update to last Doctrine Common and remove deprecation
- Switch to Simplify GitWrapper

## [0.0.50] - 2021-04-01
### Dev Release
- Job can have extra value,
- JobUnit can have extra value, and can be fetched thanks to `runWithExtra`
- JobUnit inject to Manager extras values to be reinjected into history into DeserializedJob step

## [0.0.49] - 2021-03-30
### Dev Release
- Fix DI about RunJobCommand
- Require East Foundation 5.0.2+

## [0.0.48] - 2021-03-28
### Dev Release
- Migrate to last Website version and switch to `Teknoo\East\Website\Contracts\ObjectInterface`

## [0.0.48] - 2021-03-28
### Dev Release
- Migrate to last Website version and switch to `Teknoo\East\Website\Contracts\ObjectInterface`

## [0.0.47] - 2021-03-25
### Dev Release
- Fix Project's account

## [0.0.46] - 2021-03-24
### Dev Release
- Constructor Property Promotion
- Non-capturing catches

## [0.0.45] - 2021-03-21
### Dev Release
- Migrate toPHP*
- Remove support of Symfony 4.4
- QA
- Fix license header

## [0.0.44] - 2021-03-17
### Dev Release
- Migrate ResponseInterface type hitting to MessageInterface, complete message object to pass job's ids
- Remove JobUnit prepare URL
- Migrate SendHistory/PushResult to Messenger, and 
- Migrate PSR18 SendHistory/PushResult to Messenger
- Clean Main DI, remove resolvers
- Add Handler for Messenger to send a HTTP message via a PSR 18 Driver (not enable by default)
- Add Handler for Messenger to dedicated to Symfony Console, replace Console dedicated steps
- Add Handler forwarder to enable this previous handlers only on console context.

## [0.0.43] - 2021-03-09
### Dev Release
- Clean symfony yaml indentations

## [0.0.42] - 2021-03-09
### Dev release
- Rename `Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\DispatchJob` to `DispatchJob`

## [0.0.41] - 2021-03-08
### Dev release
- Remove also Guzzle component

## [0.0.40] - 2021-03-08
### Dev release
- Remove `GuzzleTransport` and `TransportFactory` for Symfony Messenger, must be implemented into the final project
  and not provided here.

## [0.0.39] - 2021-03-07
### Dev release
- Fix deprecated in Doctrine ODM Mapping
- Account Type can set namespace to readonly
  
## [0.0.38] - 2021-03-06
### Dev release
- Replace `*AdditionalStepsInterface` entries to return an instance of the required interface instead of generic
 `AdditionalStepsInterface`.

## [0.0.37] - 2021-03-05
### Dev release
- Add Cookbook interface `EditAccountEndPointInterface` and `EditProjectEndPointInterface`
- Add abstract cookbook from EditContent Cookbook to implement previous interfaces `AbstractEditObjectEndPoint`   
- Add Symfony service `teknoo.east.paas.endpoint.admin.edit.account`
- Add Symfony service `teknoo.east.paas.endpoint.admin.edit.project`
- Switch admin routes to use these new cookbook  
- Replace `AdditionalStepsInterface:*` entries in DI by dedicated interfaces

## [0.0.36] - 2021-03-02
### Dev release
- Add Cluster::tellMeYourEnvironment to fetch cluster's env
- Add Project::listMeYourEnvironments to fetch all envs of a project

## [0.0.35] - 2021-03-02
### Dev release
- Add ObjectAccessControlInterface to NewProjectEndpoint

## [0.0.34] - 2021-02-27
### Dev release
- Update libs

## [0.0.33] - 2021-02-25
### Dev release
- Switch to East Foundation 4.0

## [0.0.32] - 2021-02-24
### Dev release
- Replace ServerRequestInterface requirement by MessageInterface requirement

## [0.0.31] - 2021-02-19
### Dev release
- Rename ImagesRepository to ImagesRegistry

## [0.0.30] - 2021-02-17
### Dev release
- Storage identifier (PVC name) can be passed in RunJob recipe.
- Recipes RunJob, NewJob and AddHistory can be completed thanks to `AdditionalStepsInterface`

## [0.0.29] - 2021-02-10
### Dev release
- Fixing NewProjectEndPoint cookbook
- Fix coobooks priorities
- Require East Website 4.1.7+

## [0.0.28] - 2021-02-09
### Dev release
- Update AdditionalStepsList to accept also BowlInterface instances

## [0.0.27] - 2021-02-09
### Dev release
- Add NewProjectEndPointInterface and NewProjectEndPoint to allow custom project creation
- AdditionalStepsList need priority for each step added and, they will be iterated on its order
- Trait to manage AdditionalStepsList for NewAccountEndPoint and NewProjectEndPoint
- Create `@teknoo.east.paas.endpoint.admin.new.project` and update project create route

## [0.0.26] - 2021-02-08
### Dev release
- Update ClusterCredentials to support only HTTP auth or Bearer auth.
  
## [0.0.25] - 2021-02-07
### Dev release
- Kubernetes client factory supports injection of Maclof repositories registry during
  client creation

## [0.0.24] - 2021-02-07
### Dev release
- Extract Kubernetes Driver factory to independent class from DI

## [0.0.23] - 2021-02-06
### Dev release
- Update to last Teknoo libraries, including Website 4.1.4
- Replace Account's property `owner` to `users` to allow several users on account

## [0.0.22] - 2021-02-04
### Dev release
- Update to last Teknoo libraries, including Website 4.1
- Fix deprecated in Symfony DI

## [0.0.21] - 2021-01-27
### Dev release
- Add namespace support, managed by account, included into the compiled deployment and pass to Kubernetes
- Add Yaml file validation, thanks to a XSD schema and an internal convertissor Yaml to XML
- Add type of cluster (`Kubernetes`, `Docker Swarm`, etc...) in project, and a Directory object to manage a set of 
  `Teknoo\East\Paas\Contracts\Cluster\ClientInterface` to manage several types of cluster in a same installation, 
  with a same PaaS, able to read a compiled deployment object to perform deployment and exposing.
- Update compilation to support the validation, the goot client selection and namespace   
- Update to Teknoo/States 4.1.7, Teknoo/Recipe 2.3, Teknoo/East Foundation 3.3 and Teknoo/Website 4.0 and implement
  new system of HTTP endpoints and controllers provided by Website to allow customisations in Project PaaS Implementation
- Update Symfony Routing, and migrate all common steps of Endpoint's recipe to Common namespace in Src  
- Custom the endpoint dedicated to New Account to allow custom step, to inject in the recipe thanks to the DI's entry 
  `AdditionalStepsInterface` to perform some custom operations, like create account on kubernetes, or registry.
- Split huge Conductor/Compilator (To tranform the file `.paas.yml` to a set a independent compiler, called by an 
  agnostic Conductor). Compiler implement an interface and compilers can be added or replace in the conductor via the DI
  to allow add resources or services in your PaaS platform.
- Transform and normalize the CompiledDeployment to an interface, to allow you implement your own CompiledDeployment 
  object to resume `.paas.yml` to execute them
- Rework the Kubernetes client to transform transcriber as independents classes, called by the client, allowing you to
  custom, change or add transcriber to the client.

## [0.0.20] - 2021-01-01
### Dev release
- Change definitions of volume in Pod
- Manage external image from image registry
- Rename DockerRepository to ImageRegistry
- Pod's containers can use embedded volumes to build a new image with files from the git repository without use
  and external volume to mount in Kubernetes.
- Add nez type of Image called EmbedddedVolumeImage and interfaces BuildableInterface and RegistrableInterface
- Change Docker to BuildKit
- Image building with BuildKit use only pipes and environments variables without write temp files
- Persistent volume

## [0.0.19] - 2020-12-06
### Dev release
- Display result and history in CLI when RunJob recipe is launch in CLI.

## [0.0.18] - 2020-12-03
### Dev release
- Official Support of PHP8

## [0.0.17] - 2020-11-11
### Dev release
- Remove composer bin in Composer hook and replace by `teknoo.east.paas.composer.phar.path` parameter

## [0.0.16] - 2020-11-11
### Dev release
- Add Symfony command to run a job (thanks to cookbook) without start a PaaS server.

## [0.0.16-beta1] - 2020-11-07
### Dev release
- Create Symfony command to run a job (thanks to cookbook) without start a PaaS server.

## [0.0.15] - 2020-10-30
### Dev release
- Increase coverage
- Improve DI
- Implements Problem+json rfc7807
- Update composer

## [0.0.14] - 2020-10-25
### Dev release
- Fix mistake in ClusterCredentialsType

## [0.0.13] - 2020-10-25
### Dev release
- Require Teknoo/States ^4.1.3
- Remove useless code : BillingInformation, PaymentInformation, DockerRepository's name

## [0.0.12] - 2020-10-23
### Dev Release
- QA

## [0.0.11] - 2020-10-22
### Dev Release
- Fix lastest typo to latest
- Ignore error on rm of previous buildx builder in template

## [0.0.10] - 2020-10-22
### Dev Release
- Fix Docker plugin DI
- Use Alpine as base image instead debian

## [0.0.9] - 2020-10-22
### Dev Release
- Migrate to East Foundation ^3.2.2
- Migrating to Docker buildx
- Add Cluster credentials user/password
- Remove name in ClusterCredentials
 
## [0.0.8] - 2020-10-13
### Dev Release
- Migrate to Recipe 2.1
- Migrate to Recipe Cookbook instead of dynamic recipe in DI
- Migrate to East Foundation 3.2

## [0.0.7] - 2020-09-18
### Dev Release
- Update QA and CI tools
- Fix tests issues on poor performance worker
- Fix for minimum requirements 
- Fix issues with SF4.4

## [0.0.6] - 2020-09-16
### Dev release
- Require teknoo/states 4.1+
- Add new Hook to clone a git repository without use git submodule

## [0.0.5] - 2020-09-10
### Dev release
- Use new version of teknoo/bridge-phpdi-symfony

## [0.0.4] - 2020-09-04
### Dev release
- Cluster credentials are not mandatory

## [0.0.3] - 2020-09-03
### Dev release
- Fix Volume building in Docker when there are several volume to make.

## [0.0.2] - 2020-09-02
### Dev release
- Change parameter name from poc to lib (replace `app.` by `teknoo.east.paas.`)
- Temp dir in docker and kubernetes clients must now defined via the DI

## [0.0.1] - 2020-09-01
### Dev Release
- First release from PoC
