#Teknoo Software - PaaS - Change Log

##[0.0.10] - 2020-10-22
###Dev Release
- Fix Docker plugin DI
- Use Alpine as base image instead debian

##[0.0.9] - 2020-10-22
###Dev Release
- Migrate to East Foundation ^3.2.2
- Migrating to Docker buildx
- Add Cluster credentials user/password
- Remove name in ClusterCredentials
 
##[0.0.8] - 2020-10-13
###Dev Release
- Migrate to Recipe 2.1
- Migrate to Recipe Cookbook instead of dynamic recipe in DI
- Migrate to East Foundation 3.2

##[0.0.7] - 2020-09-18
###Dev Release
- Update QA and CI tools
- Fix tests issues on poor performance worker
- Fix for minimum requirements 
- Fix issues with SF4.4

##[0.0.6] - 2020-09-16
###Dev release
- Require teknoo/states 4.1+
- Add new Hook to clone a git repository without use git submodule

##[0.0.5] - 2020-09-10
###Dev release
- Use new version of teknoo/bridge-phpdi-symfony

##[0.0.4] - 2020-09-04
###Dev release
- Cluster credentials are not mandatory

##[0.0.3] - 2020-09-03
###Dev release
- Fix Volume building in Docker when there are several volume to make.

##[0.0.2] - 2020-09-02
###Dev release
- Change parameter name from poc to lib (replace `app.` by `teknoo.east.paas.`)
- Temp dir in docker and kubernetes clients must now defined via the DI

##[0.0.1] - 2020-09-01
###Dev Release
- First release from PoC
