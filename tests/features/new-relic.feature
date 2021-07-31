Feature: New Relic
  In order to monitor my site's performance
  As a user
  I need to be able to view my New Relic data

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr new-relic-info.yml
  Scenario: Accessing New Relic data
    When I run "[[executable]] new-relic:info [[test_site_name]]"
    Then I should get: "--------------- --"
    And I should get: "Name"
    And I should get: "Status"
    And I should get: "Subscribed On"
    And I should get: "State"
    And I should get: "--------------- --"

  @vcr new-relic-enable.yml
  Scenario: Enabling New Relic data
    When I run "[[executable]] new-relic:enable [[test_site_name]]"
    Then I should get: "Enable New Relic"

  @vcr new-relic-disable.yml
  Scenario: Disabling New Relic data
    When I run "[[executable]] new-relic:disable [[test_site_name]]"
    Then I should get: "Disabling New Relic"
