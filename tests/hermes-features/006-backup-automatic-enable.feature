Feature: Set a Backup Schedule for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to set my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]" already exists

  Scenario: Set the backup schedule for an environment
    Given the "dev" environment of "[[test_site_name]]" does not already have backups scheduled
    When I run "terminus backup:automatic:enable [[test_site_name]].dev --day=Mon"
    Then I should get the notice: "Backup schedule successfully set."
    And the "dev" environment of "[[test_site_name]]" has backups scheduled for "Monday"
