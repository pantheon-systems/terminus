Feature: List Backup Schedule for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to show my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]" already exists

  Scenario: Show the backup schedule for an environment
    Given the "dev" environment of "[[test_site_name]]" already has backups scheduled for "Monday"
    When I run "terminus backup:automatic:info [[test_site_name]].dev"
    Then I should get: "Monday"

  Scenario: Fail to show the backup schedule for an environment when none are scheduled
    Given the "dev" environment of "[[test_site_name]]" does not already have backups scheduled
    When I run "terminus backup:automatic:info [[test_site_name]].dev"
    Then I should get the notice: "Backups are not currently scheduled to be run."
