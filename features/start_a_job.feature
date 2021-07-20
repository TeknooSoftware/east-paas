Feature: Run a new deployment on a worker
  As a developer, I need to start a new deployment, executed by a worker when it receive a normalized job
  via an http request.

  Scenario: Return an error 400 when the body is not a json
    Given I have a configured platform
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And a malformed body
    When I call the PaaS with this PUT request "/project/projectid/environment/dev/job/foo-bar/run"
    Then I must obtain an HTTP answer with this status code equals to "400".
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.mal_formed","status":400,"detail":["teknoo.east.paas.error.recipe.job.mal_formed","Syntax error"]}'

  Scenario: Return a valid JSON answer when the job exists and paas file is valid
    Given I have a configured platform
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an image builder
    And a cluster client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200".
    And with the final history at date "2018-10-01 02:03:04 UTC" in the body
