Feature: List Backup Schedule for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to show my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-schedule-get.yml
  Scenario: Show the backup schedule for an environment
    When I run "terminus backup:automatic:info [[test_site_name]].dev"
    Then I should get:
    """
    Friday
    """

  @vcr backup-schedule-get-none.yml
  Scenario: Fail to show the backup schedule for an environment when none are scheduled
    When I run "terminus backup:automatic:info [[test_site_name]].dev"
    Then I should get:
    """
    Backups are not currently scheduled to be run.
    """
