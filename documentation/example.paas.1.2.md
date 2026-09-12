Teknoo Software - PaaS library
==============================

## Example of **.paas.yaml** configuration file present into git repository to deploy

Project demo available [here](https://github.com/TeknooSoftware/east-paas-project-demo).

### What's new in v1.2

The version `v1.2` is a superset of `v1.1`: any valid `v1.1` file is a valid `v1.2` file. It only adds two
shortcuts, to reduce the size of the `.paas.yaml` file when a pod must be exposed. They generate exactly the same
deployment than the explicit definitions in `services` and `ingresses`:

* `services` in a container (`pods.<pod>.containers.<container>.services`): a list of services, exposing this pod.
  Each entry accepts only `internal`, `protocol`, `ports`, `ingress` and `enhancements`. The `pod` option is
  automatically set to the pod's name and the name of the service is generated from the pod's name and the
  container's name: `{pod}-{container}` for the first entry, `{pod}-{container}-2`, `{pod}-{container}-3`... for
  the next ones. Ports' `target` are automatically added to the container's `listen` list (so `listen` can be
  omitted if all ports are exposed via a service).
* `ingress` in a service (in the top-level `services` map or in a container's `services` list): an ingress
  definition, using the enclosing service as default service. It accepts all ingress options except `extends`
  and `service`, plus an optional `port` to select the service's listened port to use (the first listened port
  by default). The ingress is named as the service.

A generated name must not collide with an explicit definition in `services` or `ingresses` (or with another
generated name): the compilation fails with an error `... is already defined in the deployment`.

Since `v1.2`, `ingress` is a reserved key in the `.paas.yaml` file (like `services`, `ingresses`, `listen`...),
whatever the version of the file. Do not use it as name for a pod, a container, a service, a volume, a map or a
secret.

      paas: #Dedicated to compiler
        version: v1.2
        quotas: #Quotas of resources allowed for this deployment
            - category: compute
              type: cpu
              capacity: 2
              requires: 1
            - category: memory
              type: memory
              capacity: 512Mi

      #Defaults
      defaults:
        storage-provider: foo
        storage-size: 1Gi
        oci-registry-config-name: 'paas-config'
        clusters:
            cluster-east:
                storage-provider: foo
                storage-size: 2Gi
                oci-registry-config-name: 'paas-config'
            cluster-west:
                storage-provider: bar

      if{ENV=prod}: #Merge paas nod when ENV job's variables is equal to prod
          paas: #Dedicated to compiler
              version: v1
              requires:
                - set1
                - set2
              quotas:
                -   category: compute
                    type: cpu
                    capacity: 2
                    requires: 1
                -   category: memory
                    type: memory
                    capacity: 512Mi

      #Config
      maps:
          map1:
              key1: value1
              key2: ${FOO}
          map2:
              foo: bar
              bar: R{foo}
          if{ENV=prod}:
              map2:
                  key1: value1
                  key2: ${FOO}
              map3:
                  key1: value1
                  key2: ${FOO}
                  if{PROVIDER=aws}: #neested condition
                    key2: ${BAR}
    
      #Secrets provider
      secrets:
          map-vault:
              provider: map #Internal secrets, must be passed in this file
              options:
                  key1: value1
                  key2: ${FOO}
          map-vault2:
              provider: map #Internal secrets, must be passed in this file
              options:
                  hello: R{world}
          volume-vault:
              provider: map
              type: foo
              options:
                  foo: bar
                  bar: foo
    
      #Custom image, not available in the library
      images:
          foo:
              build-name: foo
              tag: latest
              path: '/images/${FOO}'
    
      #Hook to build the project before container, Called in this order
      builds:
          composer-build: #Name of the step
              composer-${FOO}: #You can use key here
                  action: install #Hook to call
                  arguments:
                      - 'no-dev'
                      - 'optimize-autoloader'
                      - 'classmap-authoritative'
          custom-hook:
              hook-id: foo bar
    
      #Volume to build to use with container
      volumes:
          extra: #Name of the volume
              local-path: "/foo/bar" #optional local path where store data in the volume
              add: #folder or file, from .paas.yaml where is located to add to the volume
                  - 'extra'
          other-name: #Name of the volume
              add: #folder or file, from .paas.yaml where is located to add to the volume
                  - 'vendor'
    
      #Pods (set of container)
      pods:
          php-pods: #podset name
              replicas: 2 #instance of pods
              requires:
                  - 'x86_64'
                  - 'avx'
              upgrade:
                  max-upgrading-pods: 2
                  max-unavailable-pods: 1
              restart-policy: always
              containers:
                  php-run: #Container name
                      image: registry.teknoo.software/php-run #Container image to use
                      version: 7.4
                      services: #Shortcut (since v1.2) to expose this pod, `pod` is automatically set
                          - internal: false #If false, a load balancer is use to access it from outside
                            protocol: 'HTTPS' #Or UDP or HTTPS
                            ports:
                                - listen: 9876 #Port listened
                                  target: 8080 #Pod's port targeted
                            #The service will be named `php-pods-php-run`, a second entry will be named
                            #`php-pods-php-run-2`, and so on.
                      volumes: #Volumes to link
                          extra:
                              from: 'extra'
                              mount-path: '/opt/extra' #Path where volume will be mount
                          app:
                              mount-path: '/opt/app' #Path where data will be stored
                              add: #folder or file, from .paas.yaml where is located to add to the volume
                                  - 'src'
                                  - 'var'
                                  - 'vendor'
                                  - 'composer.json'
                                  - 'composer.lock'
                                  - 'composer.phar'
                              writables:
                                  - 'var/*'
                          data: #Persistent volume, can not be pre-populated
                              mount-path: '/opt/data'
                              persistent: true
                              storage-size: 3Gi
                          data-replicated: #Persistent volume, can not be pre-populated
                              name: data-replicated #to share this volume between
                              write-many: true
                              mount-path: '/opt/data-replicated'
                              persistent: true
                              storage-provider: 'replicated-provider'
                              storage-size: 3Gi
                          map:
                              mount-path: '/map'
                              from-map: 'map2'
                          vault:
                              mount-path: '/vault'
                              from-secret: 'volume-vault'
                      variables: #To define some environment variables
                          SERVER_SCRIPT: '${SERVER_SCRIPT}'
                          from-maps:
                              KEY0: 'map1.key0'
                          import-maps:
                              - 'map2'
                          from-secrets: #To fetch some value from secret/vault
                              KEY1: 'map-vault.key1'
                              KEY2: 'map-vault.key2'
                          import-secrets:
                              - 'map-vault2'
                      healthcheck:
                          initial-delay-seconds: 10
                          period-seconds: 30
                          probe:
                              command: ['ps', 'aux', 'php']
                      resources:
                          - type: cpu
                            require: 0.2
                            limit: 0.5
                          - type: memory
                            require: 64Mi
                            limit: 125Mi
          shell:
              replicas: 1
              containers:
                  sleep:
                      image: registry.hub.docker.com/bash
                      version: alpine
          demo:
              replicas: 1
              upgrade:
                  strategy: recreate
              security:
                  fs-group: 1000
              containers:
                  nginx:
                      image: registry.hub.docker.com/library/nginx
                      version: alpine
                      #`listen` is omitted, it is automatically filled with 8080 and 8181 from the service's ports
                      services: #The service will be named `demo-nginx`
                          - ports:
                                - listen: 8080 #Port listened
                                  target: 8080 #Pod's port targeted
                                - listen: 8181 #Port listened
                                  target: 8181 #Pod's port targeted
                            ingress: #Shortcut (since v1.2), the ingress is named `demo-nginx`
                                host: demo-paas.teknoo.software
                                tls:
                                    secret: "demo-vault" #Configure the orchestrator to fetch value from vault
                                #The default service is `demo-nginx`, on the first listened port (8080)
                                meta:
                                    letsencrypt: true
                                    annotations:
                                        foo2: bar
                                aliases:
                                    - demo-paas.teknoo.software
                                    - alias1.demo-paas.teknoo.software
                                    - alias1.demo-paas.teknoo.software
                                    - alias2.demo-paas.teknoo.software
                                paths:
                                    - path: /php
                                      service:
                                          name: php-pods-php-run #Generated name of the service defined above
                                          port: 9876
                      volumes:
                          www:
                              mount-path: '/var'
                              add:
                                  - 'nginx/www'
                          config:
                              mount-path: '/etc/nginx/conf.d/'
                              add:
                                  - 'nginx/conf.d/default.conf'
                      healthcheck:
                          initial-delay-seconds: 10
                          period-seconds: 30
                          probe:
                              http:
                                  port: 8080
                                  path: '/status'
                                  is-secure: true
                          threshold:
                              success: 3
                              failure: 2
                  waf:
                      image: registry.hub.docker.com/library/waf
                      version: alpine
                      listen: #Port listen by the container
                          - 8181
                      healthcheck:
                          initial-delay-seconds: 10
                          period-seconds: 30
                          probe:
                              tcp:
                                  port: 8181
                  blackfire:
                      image: 'blackfire/blackfire'
                      version: '2'
                      listen:
                          - 8307
                      variables:
                          BLACKFIRE_SERVER_ID: 'foo'
                          BLACKFIRE_SERVER_TOKEN: 'bar'

      #Job
      jobs:
          job-init:
              completions:
                  mode: indexed #similar to indexed completion in kubernetes
                  count: 3 #to launch 3 jobs
                  time-limit: 10 #time limit in second to set timeout the job (not a pod, but all pods)
                  shelf-life: 20 #optional time in second to delete the job when it is completed (successful or not), 120s by default
              is-parallel: true #To launch 3*2 pods in parallel or sequential
              pods:
                  init-var:
                      replicas: 1
                      containers:
                          init:
                              image: registry.hub.docker.com/bash
                              version: alpine
                  update:
                      containers:
                          update:
                              image: registry.hub.docker.com/alpine
                              version: alpine
          job-translation:
              planning: during-deployment #optional, default value
              completions:
                  success-on: [0, 5]
                  fail-on: [1]
                  limit-on: "php-translation"
              pods:
                  php-translation:
                      restart-policy: on-failure
                      containers:
                          php-translation:
                              image: registry.teknoo.software/php-cli #Container image to use
                              version: 7.4
                              volumes: #Volumes to link
                                  extra:
                                      from: 'extra'
                                      mount-path: '/opt/extra' #Path where volume will be mount
                                  app:
                                      mount-path: '/opt/app' #Path where data will be stored
                                      add: #folder or file, from .paas.yaml where is located to add to the volume
                                          - 'src'
                                          - 'var'
                                          - 'vendor'
                                          - 'composer.json'
                                          - 'composer.lock'
                                          - 'composer.phar'
                                      writables:
                                          - 'var/*'
                                  data: #Persistent volume, can not be pre-populated
                                      mount-path: '/opt/data'
                                      persistent: true
                                      storage-size: 3Gi
                                  data-replicated: #Persistent volume, can not be pre-populated
                                      name: data-replicated #to share this volume between
                                      write-many: true
                                      mount-path: '/opt/data-replicated'
                                      persistent: true
                                      storage-provider: 'replicated-provider'
                                      storage-size: 3Gi
                                  map:
                                      mount-path: '/map'
                                      from-map: 'map2'
                                  vault:
                                      mount-path: '/vault'
                                      from-secret: 'volume-vault'
          job-backup:
              planning: scheduled #To create a cron job
              schedule: '0 0 /3 * * *' #to schedule the cron job
              pods:
                  backup:
                      containers:
                          backup:
                              image: registry.hub.docker.com/backup
                              version: alpine



      #Pods expositions
      services:
          demo-udp: #Service name
              pod: "demo" #Pod name, use service name by default
              protocol: 'UDP' #Or UDP or HTTPS'
              ports:
                  - listen: 6666 #Port listened
                    target: 6666 #Pod's port targeted
              ingress: #Shortcut (since v1.2), the ingress is named `demo-udp`
                  host: demo-udp.teknoo.software
                  port: 6666 #Optional, the service's listened port to use, the first one by default
                  tls:
                      secret: "demo-vault" #Configure the orchestrator to fetch value from vault
    
      #Ingresses configuration
      ingresses:
          demo-secure: #rule name
              host: demo-secure.teknoo.software
              https-backend: true
              tls:
                  secret: "demo-vault" #Configure the orchestrator to fetch value from vault
              service: #default service
                  name: demo-nginx #Generated name of the service defined in the container `nginx` of the pod `demo`
                  port: 8181
