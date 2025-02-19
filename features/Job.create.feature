Feature: Create a job deployment about project
  In order to deploy a project
  As an developer
  I want to create a new job to run it on worker and deploy project

  Start a new job to deploy my application contained into my repository to the designed infrastructure configured into
  PaaS, following the configuration defined into this same repository. This job must be dispatched to workers to process
  to this deployment.

  Scenario: From the API, create a new job from an non existent project and get a 404 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/anotherid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.project.not_found","status":404,"detail":["teknoo.east.paas.error.recipe.project.not_found","Object not found"]}'

  Scenario: From the API, create a new job without define the environment and get a 400 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/dev/job/create"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.job.not_validated","status":400,"detail":["teknoo.east.paas.error.job.not_validated"]}'

  Scenario: From the API, create a new job from a non full-filler project with not full filled and get a 501 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "501"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.project.not_executable","status":501,"detail":["teknoo.east.paas.error.project.not_executable"]}'

  Scenario: From the API, create a new job but the creation too long and get a 504 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And simulate a very slowly database
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "504"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.job.save_error","status":504,"detail":["teknoo.east.paas.job.save_error","Error, time limit exceeded"]}'

  Scenario: From the API, create a new job and sent it to the worker and get a valid json
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized in the body
    And all messages must be not encrypted

  Scenario: From the API, create a new job with environments variables and sent it to the worker and get a valid json
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create" with body '{"foo": "bar", "bar": "foo"}' and content type defined to "application/json"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized in the body with variables '{"foo": "bar", "bar": "foo"}'
    And all messages must be not encrypted

  Scenario: From the API, create a new job with environments variables on account subjet to quota and sent it to the
  worker and get a valid json
    Given I have a configured platform
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create" with body '{"foo": "bar", "bar": "foo"}' and content type defined to "application/json"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized in the body with variables '{"foo": "bar", "bar": "foo"}' and quotas defined
    And all messages must be not encrypted

  Scenario: From the API, create a new job with environments variables and sent it to the worker via encrypted messages
  and get a valid json
    Given I have a configured platform
    And encryption capacities between servers and agents
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create" with body '{"foo": "bar", "bar": "foo"}' and content type defined to "application/json"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized in the body with variables '{"foo": "bar", "bar": "foo"}'
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the PaaS could create a job and send it to worker with hierarchical namespace.
    Given I have a configured platform
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized with hnc in the body
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the PaaS could create a job with env vars, quota and send it to
    worker with hierarchical namespace.
    Given I have a configured platform
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create" with body '{"foo": "bar", "bar": "foo"}' and content type defined to "application/json"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the job normalized with hnc in the body with variables '{"foo": "bar", "bar": "foo"}' and quotas defined
    And all messages must be not encrypted
