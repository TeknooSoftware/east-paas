Feature: Execute a job to deploy a project on a Docker Compose host through Ansible
  In order to deploy a project
  As an developer running a worker
  I want to deploy a project on a Docker host over SSH, expressed as a Compose Specification and exposed
  through Traefik

  The PaaS library must normalize and serialize the job with all required informations to allow the worker to fetch a
  project on a repository, get vendors and dependencies, compile and prepare it, build images, push them on registries
  and deploy the project on a Docker host via Ansible, generating a Compose Specification file and a Traefik dynamic
  configuration file.

  Scenario: From the API, for a Docker Compose host, run a job on a project using prefix with a PaaS file and get a
  normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a docker-compose orchestrator
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 23 in the body
    And some docker compose configuration has been created
    And some traefik configuration has been created
    And all messages must be not encrypted

  Scenario: From the API, for a Docker Compose host, run a job on a project using prefix with a PaaS file using
  conditions and get a normalized job's history
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with conditions
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a docker-compose orchestrator
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 23 in the body
    And some docker compose configuration has been created
    And some traefik configuration has been created
    And all messages must be not encrypted

  Scenario: From the API, for a Docker Compose host, run a job on a project with a PaaS file using extends and get a
  normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a docker-compose orchestrator
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 23 in the body
    And some docker compose configuration has been created
    And some traefik configuration has been created
    And all messages must be not encrypted

  Scenario: From the API, for a Docker Compose host, run a job on a project using prefix with a PaaS file using extends
  and get a normalized job's history
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a docker-compose orchestrator
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 23 in the body
    And some docker compose configuration has been created
    And some traefik configuration has been created
    And all messages must be not encrypted
