Feature: New Relic
  In order to monitor my site's performance
  As a user
  I need to be able to view my New Relic data

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_new-relic_status
  Scenario: Accessing New Relic data
    When I run "terminus site new-relic:status --site=[[test_site_name]]"
    Then I should get: "Subscribed"

  @vcr site_new-relic_enable
  Scenario: Enabling New Relic data
    When I run "terminus site new-relic:enable --site=[[test_site_name]]"
    Then I should get one of the following: "New Relic enabled., This site already has a Pantheon-created NewRelic account."

  @vcr site_new-relic_disable
  Scenario: Disabling New Relic data
    When I run "terminus site new-relic:disable --site=[[test_site_name]]"
    Then I should get: "New Relic disabled."
