Feature: Push an entry to a specific job history
  As a developer, I need push a new entry in a job's history to log actions.
  Entries are available into job, know as History. A Job keep only the last entry but references the previous.

  Scenario: Return an error 404 when the project does not exist
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/anotherid/environment/prod/job/foo-bar/log"
    Then I must obtain an HTTP answer with this status code equals to "404".
    And with this body answer, in json, '{"error":true, "message":"teknoo.paas.error.recipe.project.not_found", "extra":"Object not found"}'

  Scenario: Return an error 404 when the job does not exist
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/projectid/environment/prod/job/foo-bar/log"
    Then I must obtain an HTTP answer with this status code equals to "404".
    And with this body answer, in json, '{"error":true, "message":"teknoo.paas.error.recipe.job.not_found", "extra":"Object not found"}'

  Scenario: Return a valid JSON answer when the job exists
    Given I have a configured platform
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I push a new message "foobar" at "2018-01-01 01:00:00 UTC" to "/project/projectid/environment/prod/job/jobid/log"
    Then I must obtain an HTTP answer with this status code equals to "200".
    And with the history "foobar" at date "2018-01-01 01:00:00 UTC" normalized in the body.
