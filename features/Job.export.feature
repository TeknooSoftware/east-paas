Feature: Export a job deployment about a project
  In order to deploy a project
  As an developer
  I want to export a job

  The job exported as json can me fully descripted, or only a short digest or without all credentials and keys data.

  Scenario: From the API, get a full JSON export job
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
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I export the job "jobid" with "default" data
    Then I must obtain a "full described" job

  Scenario: From the API, get a full JSON export job with quotas limits
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
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC" and with 2 vcore and 512Mo memory quotas
    When I export the job "jobid" with "default" data
    Then I must obtain a "full described with quotas" job

  Scenario: From the API, get a desensitized JSON export job
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
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I export the job "jobid" with "api" data
    Then I must obtain a "desensitized described" job

  Scenario: From the API, a digest JSON export job
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
    And a cluster "behat-cluster" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I export the job "jobid" with "digest" data
    Then I must obtain a "digest described" job
