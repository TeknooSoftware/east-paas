Feature: Execute a job to deploy a project on a Kubernetes cluster
  In order to deploy a project
  As an developer running a worker
  I want to deploy a project in a Kubernetes cluster

  The PaaS library must normalize and serialize the job with all required informations to allow the worker to fetch a
  project on a repository, get vendors and dependencies, compile and prepare it, build images, push them on registries
  and deploy the project on a Kubernetes cluster.

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file and get a
  normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using
  conditions and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with conditions
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using
  conditions, on encrypted messages between workers and get a normalized job's history
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
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using jobs and
  get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with jobs
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 43 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using jobs, on
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
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 43 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file with unavailable extends and
  get a 400 error
    Given I have a configured platform
    And the platform is booted
    And a project with a paas file using extends
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
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.extends-not-available:pods:php-pods-extends","status":400,"detail":["teknoo.east.paas.error.recipe.job.extends-not-available:pods:php-pods-extends"]}'
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using extends and get a
  normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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

  Scenario: From the API, for a Kubernetes cluster, run a too long job on a project with a PaaS file using extends and
  a 500 error
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using extends
  and get a normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

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

  Scenario: From the API, for a Kubernetes cluster, run a job on a project with a PaaS file using extends, on cluster
  supporting hierarchical namespace and get a normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file, on cluster
  supporting hierarchical namespace and get a normalized job's history
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
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: From the API, for a Kubernetes cluster, run a job on a project using prefix with a PaaS file using extends,
  on cluster supporting hierarchical namespace and get a normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted
