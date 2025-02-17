Feature: Create a job, aka a new deployment
  As a developer, I need to start a new job to deploy my application contained into my repository to
  the designed infrastructure configured into PaaS, following the configuration defined into this same repository.
  This job must be dispatched to workers to process to this deployment.

  Scenario: Return an error 404 when the project does not exist
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And the platform is booted
    When I call the PaaS with this PUT request "/project/anotherid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.project.not_found","status":404,"detail":["teknoo.east.paas.error.recipe.project.not_found","Object not found"]}'

  Scenario: Return an error 400 when the environment is not available in the project
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

  Scenario: Return an error 501 when the project is not fully filled in the PaaS
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "501"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.project.not_executable","status":501,"detail":["teknoo.east.paas.error.project.not_executable"]}'

  Scenario: Return an error 500 when the creation take too long
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a oci repository
    And a repository on the url "https://github.com/foo/bar"
    And simulate a very slowly database
    And the platform is booted
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "500"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.job.save_error","status":500,"detail":["teknoo.east.paas.job.save_error","Error, time limit exceeded"]}'

  Scenario: Return a valid JSON answer when the PaaS could create a job and send it to worker.
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

  Scenario: Return a valid JSON answer when the PaaS could create a job with env vars and send it to worker.
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

  Scenario: Return a valid JSON answer when the PaaS could create a job with env vars and quota and send it to worker.
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

  Scenario: Return a valid JSON answer when the PaaS could create a job with env vars and send it to worker
    with encrypted message
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
