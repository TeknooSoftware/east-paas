Feature: Start a job, by running a new deployment on a worker for kubernetes
  As a developer, I need to start a new deployment, executed by a worker when it receive a normalized job
  via an http request.

  Scenario: Return an error 400 when the body is not a json
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a malformed body
    When I call the PaaS with this PUT request "/project/projectid/environment/dev/job/foo-bar/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.mal_formed","status":400,"detail":["teknoo.east.paas.error.recipe.job.mal_formed","Syntax error"]}'

  Scenario: Return a valid JSON answer when the job exists and paas file with extends not available
    Given I have a configured platform
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.job.extends-not-available:pods:php-pods-extends","status":400,"detail":["teknoo.east.paas.error.recipe.job.extends-not-available:pods:php-pods-extends"]}'
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists and paas file is valid
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists and paas file is valid with encrypted message
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the job with quota exists and paas file is valid without resources defined
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file without resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job with encrypted message and quota exists and paas file is valid without resources defined
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file without resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the job with quota exists and paas file is valid with partial resources defined
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with partial resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job with encrypted message and quota exists and paas file is valid with partial resources defined
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with partial resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the job with quota exists and paas file is valid with resources defined
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job with encrypted message and quota exists and paas file is valid with resources defined
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with resources
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the job with quota exists and paas file is valid with quota exceeded
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file with limited quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":400,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","Error, available capacity for `memory` is `6Mi` (soft defined limit), but limited `32Mi`"]}'
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job with encrypted message and quota exists and paas file is valid with quota exceeded
    Given I have a configured platform
    And encryption capacities between servers and agents
    And the platform is booted
    And a project with a complete paas file with limited quota
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And quotas defined for this account
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "400"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"teknoo.east.paas.error.recipe.configuration.compilation_error","status":400,"detail":["teknoo.east.paas.error.recipe.configuration.compilation_error","Error, available capacity for `memory` is `6Mi` (soft defined limit), but limited `32Mi`"]}'
    And all messages must be encrypted

  Scenario: Return a valid JSON answer when the job exists and paas file with extends is valid
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return an error 500 when the job takes too long
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    And simulate a too long image building
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "500"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"Error, time limit exceeded","status":500,"detail":["Error, time limit exceeded"]}'
    And all messages must be not encrypted

  Scenario: Return an error 500 when the job takes too long with a paas file extends
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    And simulate a too long image building
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "500"
    And with this body answer, the problem json, '{"type":"https:\/\/teknoo.software\/probs\/issue","title":"Error, time limit exceeded","status":500,"detail":["Error, time limit exceeded"]}'
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with prefix and paas file is valid
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with prefix and paas file with extends is valid
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC"
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with hierarchical namespace and paas file is valid
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC" and HNC
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with hierarchical namespace and paas file with extends is valid
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC" and HNC
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with hierarchical namespace and prefix and paas file is valid
    Given I have a configured platform
    And the platform is booted
    And a project with a complete paas file
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC" and HNC
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted

  Scenario: Return a valid JSON answer when the job exists with hierarchical namespace and prefix and paas file with extends is valid
    Given I have a configured platform
    And extensions libraries provided by administrators
    And the platform is booted
    And a project with a paas file using extends
    And a job workspace agent
    And a git cloning agent
    And a composer hook as hook builder
    And an OCI builder
    And a kubernetes client
    And a cluster supporting hierarchical namespace
    And A consumer Account "fooBar"
    And a project on this account "fooBar Project" with the id "projectid" and a prefix "a-prefix"
    And a cluster "kubernetes" dedicated to the environment "prod"
    And a repository on the url "https://github.com/foo/bar"
    And a job with the id "jobid" at date "2018-01-01 00:00:00 UTC" and HNC
    When I run a job "jobid" from project "projectid" to "/project/projectid/environment/prod/job/jobid/run"
    Then I must obtain an HTTP answer with this status code equals to "200"
    And with the final history at date "2018-10-01 02:03:04 UTC" and with the serial at 36 in the body
    And some Kubernetes manifests have been created
    And all messages must be not encrypted
