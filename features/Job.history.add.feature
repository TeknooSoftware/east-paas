Feature: Logs events from workers about a job deployment of a project
  In order to deploy a project
  As an developer running a worker
  I want to log events some from the worker

  Push a new event in a job's history to log actions. Events are available into job, know as History.
  A Job keep only the last entry but references the previous.

  Scenario: From the API, push an event about a non existent project and get a 404 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And the platform is booted
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/anotherid/environment/prod/job/foo-bar/log"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.project.not_found","status":404,"detail":["teknoo.east.paas.error.recipe.project.not_found","Object not found"]}'

  Scenario: From the API, push an event about a non existent job and get a 404 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And the platform is booted
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/projectid/environment/prod/job/foo-bar/log"
    Then I must obtain an HTTP answer with this status code equals to "404"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.not_found","status":404,"detail":["teknoo.east.paas.error.recipe.job.not_found","Object not found"]}'

  Scenario: From the API, push an event but the update take too long about and get a 504 error
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    And simulate a very slowly database
    And the platform is booted
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/projectid/environment/prod/job/jobid/log"
    Then I must obtain an HTTP answer with this status code equals to "504"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.job.save_error","status":504,"detail":["teknoo.east.paas.job.save_error","Error, time limit exceeded"]}'

  Scenario: From the API, push an event about a job and get a normalized event
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    And the platform is booted
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/projectid/environment/prod/job/jobid/log"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the history "foobar" at date "2018-01-01 01:00:00 UTC" normalized in the body
