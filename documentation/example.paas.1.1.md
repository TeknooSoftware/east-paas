Teknoo Software - PaaS library
==============================

## Example of **.paas.yaml** configuration file present into git repository to deploy

Project demo available [here](https://github.com/TeknooSoftware/east-paas-project-demo).

      paas: #Dedicated to compiler
        version: v1.1
        resources:
            - category: compute
              type: cpu
              capacity: 2
              require: 1
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
              containers:
                  php-run: #Container name
                      image: registry.teknoo.software/php-run #Container image to use
                      version: 7.4
                      listen: #Port listen by the container
                          - 8080
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
                      listen: #Port listen by the container
                          - 8080
                          - 8181
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
          php-service: #Service name
              pod: "php-pods" #Pod name, use service name by default
              internal: false #If false, a load balancer is use to access it from outside
              protocol: 'TCP' #Or UDP
              ports:
                  - listen: 9876 #Port listened
                    target: 8080 #Pod's port targeted
          demo: #Service name
              ports:
                  - listen: 8080 #Port listened
                    target: 8080 #Pod's port targeted
                  - listen: 8181 #Port listened
                    target: 8181 #Pod's port targeted
    
      #Ingresses configuration
      ingresses:
          demo: #rule name
              host: demo-paas.teknoo.software
              tls:
                  secret: "demo-vault" #Configure the orchestrator to fetch value from vault
              service: #default service
                  name: demo
                  port: 8080
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
                        name: php-service
                        port: 9876
          demo-secure: #rule name
              host: demo-secure.teknoo.software
              https-backend: true
              tls:
                  secret: "demo-vault" #Configure the orchestrator to fetch value from vault
              service: #default service
                  name: demo
                  port: 8181