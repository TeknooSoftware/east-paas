Feature: Export a job
  As a developer, I need to export a job. The job exported as json can me fully descripted, or only a short digest or
  without all credentials and keys data

  Scenario: Return a full JSON export job
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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
    When I export the job "jobid" with "all" data
    Then I must obtain a "full described" job

  Scenario: Return a desensitized JSON export job
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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
    When I export the job "jobid" with "api" data
    Then I must obtain a "desensitize described" job

  Scenario: Return a digest JSON export job
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
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
    When I export the job "jobid" with "digest" data
    Then I must obtain a "digest described" job
