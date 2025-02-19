Feature: Execute a job to deploy a project on a cluster
  In order to deploy a project
  As an developer running a worker
  I want to deploy a project in a cluster

  The PaaS library must normalize and serialize the job with all required informations to allow the worker to fetch a
  project on a repository, get vendors and dependencies, compile and prepare it, build images, push them on registries
  and deploy the project on a cluster.

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and get a
  normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server, on encrypted
  messages between workers and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS and in PaaS file and
  get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file with defaults
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and in PaaS
  file, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with defaults
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and another
  defaults dedicated to the cluster in PaaS file and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file with defaults for the cluster
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and another
  defaults dedicated to the cluster in PaaS file, on encrypted messages between workers and get a normalized job's
  history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with defaults for the cluster
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server, overwritten
  in the job and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have generic defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server, overwritten
  in the job, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have generic defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and some
  defaults dedicated to the cluster are overwritten in the job and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have cluster's defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server and some
  defaults dedicated to the cluster are overwritten in the job, on encrypted messages between workers and get a
  normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have cluster's defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server, another
  defaults dedicated to the cluster in PaaS file and some defaults dedicated to the cluster are overwritten in the job,
  on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And the platform is booted
    And a project with a complete paas file with defaults for the cluster
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have cluster's defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be not encrypted

  Scenario: From the API, run a job on a project with a PaaS file with defaults values in the PaaS server, another
  defaults dedicated to the cluster in PaaS file and some defaults dedicated to the cluster are overwritten in the job,
  on encrypted messages between workers, on encrypted messages between workers and get a normalized job's history
    Given I have a configured platform
    And some defaults to compile jobs
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with defaults for the cluster
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the next job will have cluster's defaults set in job unit
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 20 in the body
    And all messages must be encrypted
