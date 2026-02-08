Feature: Execute a job to deploy a project on a Kubernetes cluster
  In order to deploy a project
  As an developer running a worker
  I want to deploy a project in a Kubernetes cluster

  The PaaS library must normalize and serialize the job with all required informations to allow the worker to fetch a
  project on a repository, get vendors and dependencies, compile and prepare it, build images, push them on registries
  and deploy the project on a Kubernetes cluster.

  Scenario: From the API, for a Kubernetes cluster, push an non json request ang get a 400 error
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a malformed body
    When I call the PaaS with this PUT request "/project/projectid/environment/dev/job/foo-bar/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.mal_formed","status":400,"detail":["teknoo.east.paas.error.recipe.job.mal_formed","Syntax error"]}'

  Scenario: From the API, for a Kubernetes cluster, run a too long job on a project with a PaaS file and a 500 error
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    And simulate a too long image building
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "500"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"Error, time limit exceeded","status":500,"detail":["Error, time limit exceeded"]}'
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file and get a normalized job's
  history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file, on encrypted messages
  between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using conditions and get a
  normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with conditions
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using conditions but file
  has wrong version and get an error
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with conditions with wrong version
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, 'if{ENV=prod} error'

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using conditions, on
  encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with conditions
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using jobs and get a
  normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with jobs
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 43 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using jobs but the PaaS file
  has a wrong version and get an error
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with jobs with wrong version
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, 'job validation error'

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using jobs, on
  encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with jobs
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 43 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file without defined resources
  on a cluster with quota and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file without resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file without defined resources
  on a cluster with quota, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file without resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with partial defined
  resources on a cluster with quota and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with partial resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with partial defined
  resources on a cluster with quota, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with partial resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  on a cluster with quota and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  on a cluster with quota, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  via relative quota on a cluster with quota and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with resources and relative quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And larges quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  via relative quota on a cluster with quota, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with resources and relative quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And larges quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  on a cluster with quota, but required resources exceed quota and get a 404 error
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with limited quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":400,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","Error, remaining available capacity for `memory` is `6Mi` (soft defined limit), but limit required is `32Mi`"]}'
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with full defined resources
  on a cluster with quota, on encrypted messages between workers but required resources exceed quota and get a 404 error
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with limited quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":400,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","Error, remaining available capacity for `memory` is `6Mi` (soft defined limit), but limit required is `32Mi`"]}'
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file, on cluster supporting
  hierarchical namespace and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file without validated
  requirements and get a 404 error
    Given I have a configured platform
    And the platform is booted
    And a project with a paas file with requirements
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":404,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","These requirements `set1`, `set2` are not validated"]}'
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with validated requirements
  and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a paas file with requirements
    And validator for requirements
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file without validated
  requirements, on encrypted messages between workers and get a 404 error
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a paas file with requirements
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":404,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","These requirements `set1`, `set2` are not validated"]}'
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with validated requirements,
  on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a paas file with requirements
    And validator for requirements
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with enhancements and
  validated requirements, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a paas file with requirements and enhancements
    And validator for requirements
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster with Traefik ingress controller, run a job with custom backend
  protocol annotation mapper
    Given I have a configured platform
    And a custom backend protocol annotation mapper for Traefik
    And the platform is booted
    And a project with a paas file using Traefik backend
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted
