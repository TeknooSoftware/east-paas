# Teknoo Software - PaaS - Change Log

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
