Feature: New Relic
  In order to monitor my site's performance
  As a user
  I need to be able to manipulate my New Relic data

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_new-relic
  Scenario: Accessing New Relic data
    When I run "terminus site new-relic info --site=[[test_site_name]]"
    Then I should get: "New Relic is not enabled."

  @vcr site_new-relic_enable
  Scenario: Enabling New Relic Pro
    When I run "terminus site new-relic enable --site=[[test_site_name]]"
    Then I should get:
    """
    Enabled New Relic
    """

  @vcr site_new-relic_disable
  Scenario: Disabling New Relic Pro
    When I run "terminus site new-relic disable --site=[[test_site_name]]"
    Then I should get:
    """
    Disabled New Relic
    """
