Feature: Create a job
  As a developer, I need to start a new job to deploy my application contained into my repository to
  the designed infrastructure configured into PaaS, following the configuration defined into this same repository.
  This job must be dispatched to workers to process to this deployment.

  Scenario: Return an error 404 when the project does not exist
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    When I call the PaaS with this PUT request "/project/anotherid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "404".
    And with this body answer, in json, '{"error":true, "message":"teknoo.east.paas.error.recipe.project.not_found", "extra":"Object not found"}'

  Scenario: Return an error 400 when the environment is not available in the project
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a doctrine repository
    And a repository on the url "https://github.com/foo/bar"
    When I call the PaaS with this PUT request "/project/projectid/environment/dev/job/create"
    Then I must obtain an HTTP answer with this status code equals to "400".
    And with this body answer, in json, '{"error":true, "message":"teknoo.east.paas.error.job.not_validated", "extra":"teknoo.east.paas.error.job.not_validated"}'

  Scenario: Return an error 501 when the project is not fully filled in the PaaS
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a doctrine repository
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "501".
    And with this body answer, in json, '{"error":true,"message":"teknoo.east.paas.error.project.not_executable", "extra":"teknoo.east.paas.error.project.not_executable"}'

  Scenario: Return a valid JSON answer when the PaaS could create a job and send it to worker.
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a doctrine repository
    And a repository on the url "https://github.com/foo/bar"
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create"
    Then I must obtain an HTTP answer with this status code equals to "200".
    And with the job normalized in the body.

  Scenario: Return a valid JSON answer when the PaaS could create a job with env vars and send it to worker.
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a doctrine repository
    And a repository on the url "https://github.com/foo/bar"
    When I call the PaaS with this PUT request "/project/projectid/environment/prod/job/create" with body '{"foo": "bar", "bar": "foo"}' and content type defined to "application/json"
    Then I must obtain an HTTP answer with this status code equals to "200".
    And with the job normalized in the body with variables '{"foo": "bar", "bar": "foo"}'
