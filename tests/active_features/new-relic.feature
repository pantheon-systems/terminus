Feature: New Relic
  In order to monitor my site's performance
  As a user
  I need to be able to view my New Relic data

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_new-relic_status
  Scenario: Accessing New Relic data
    When I run "terminus site new-relic:status [[test_site_name]]"
    Then I should get: "fail"

  @vcr site_new-relic_enable
  Scenario: Enabling New Relic data
    When I run "terminus site new-relic:enable [[test_site_name]]"
    Then I should get one of the following: "fail, failo"

  @vcr site_new-relic_disable
  Scenario: Disabling New Relic data
    When I run "terminus site new-relic:disable [[test_site_name]]"
    Then I should get: "fail"
