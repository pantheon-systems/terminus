Feature: Check whether an environment has upstream updates to apply
  In order to easily maintain my site
  As a user
  I need to be able to check whether my environment has upstream updates to apply.

  Background: I am authenticated and I have a site named [[test_site_name]] using Git mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"

  @vcr env-deploy-no-changes.yml
  Scenario: Check for upstream update status when it's current
    When I run "terminus upstream:updates:status [[test_site_name]].test"
    Then I should get: "status: current"

  @vcr env-deploy-no-changes.yml
  Scenario: Check for upstream update status when it's outdated
    When I run "terminus upstream:updates:status [[test_site_name]].live"
    Then I should get: "status: outdated"
