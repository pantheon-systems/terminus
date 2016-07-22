Feature: New Relic
  In order to monitor my site's performance
  As a user
  I need to be able to set and view my New Relic status

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_newrelic
  Scenario: Accessing New Relic status
    When I run "terminus site newrelic status --site=[[test_site_name]]"
    Then I should get: "New Relic is disabled."

  Scenario: Setting New Relic status
    When I run "terminus site newrelic enable --site=[[test_site_name]]"
    Then I should get: "New Relic is enabled."
