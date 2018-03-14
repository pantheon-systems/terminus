Feature: Checking metrics for an environment
  In order to determine how much traffic is being sent to my site
  As a user
  In need to be able to check the metrics logs

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr metrics.yml
  Scenario: Checking metrics
    When I run "terminus whoami"
    Then I should get: "@"
    When I run "terminus site:info [[test_site_name]]"
    Then I should get: "couture-costume"
    When I run "terminus alpha:env:metrics [[test_site_name]].live --fields=value"
    Then I should get: " -------"
    And I should get: "  Value"
    And I should get: " -------"
    And I should get: "1197"
