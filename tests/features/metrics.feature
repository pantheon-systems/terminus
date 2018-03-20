Feature: Checking metrics for an environment
  In order to determine how much traffic is being sent to my site
  As a user
  In need to be able to check the metrics logs

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr metrics.yml
  Scenario: Checking metrics
    When I run "terminus alpha:env:metrics [[test_site_name]]"
    Then I should get: "Period       Visits   Pages Served"
    And I should get: "2018-03-14      159          1,335"
    And I should get: "2018-03-15      172            650"
