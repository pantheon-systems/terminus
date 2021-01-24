Feature: Cancel a Backup Schedule for a Site
  In order to manage the security of my site content
  As a user
  I need to be able to cancel my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]" already exists

  Scenario: Cancel the backup schedule for an environment
    Given the "dev" environment of "[[test_site_name]]" already has backups scheduled
    When I run "terminus backup:automatic:disable [[test_site_name]].dev"
    Then I should get the notice: "Backup schedule successfully canceled."
    And the "dev" environment of "[[test_site_name]]" does not have backups scheduled
