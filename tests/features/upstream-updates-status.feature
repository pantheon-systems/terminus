Feature: Check an environment's upstream update status
  In order to easily maintain my site
  As a user
  I need to be able to check my environments' upstream update status.

  Background: I am authenticated and I have a site named [[test_site_name]] using Git mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"

  @vcr upstream-updates.yml
  Scenario: Check for upstream update status when it's current
    When I run "terminus upstream:updates:status [[test_site_name]].dev"
    Then I should get: "current"

  @vcr upstream-update-list.yml
  Scenario: Check for upstream updates status when it's outdated
    When I run "terminus upstream:updates:status [[test_site_name]].dev"
    Then I should get: "outdated"
