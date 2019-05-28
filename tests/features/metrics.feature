Feature: Checking metrics for an environment
  In order to determine how much traffic is being sent to my site
  As a user
  In need to be able to check the metrics logs

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr metrics.yml
  Scenario: Checking aggregated metrics
    When I run "terminus alpha:env:metrics [[test_site_name]] --datapoints=2"
    Then I should get: "Period       Visits   Pages Served"
    And I should get: "2018-04-10    7,357         14,606"
    And I should get: "2018-04-11    5,569         10,981"

  @vcr metrics.yml
  Scenario: Checking metrics for live env
    When I run "terminus alpha:env:metrics [[test_site_name]].live --datapoints=2"
    Then I should get: "Period       Visits   Pages Served"
    And I should get: "2018-04-10    7,353         14,431"
    And I should get: "2018-04-11    5,565         10,845"