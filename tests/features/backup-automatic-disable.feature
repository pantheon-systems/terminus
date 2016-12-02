Feature: Cancel a Backup Schedule for a Site
  In order to manage the security of my site content
  As a user
  I need to be able to cancel my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-schedule-cancel.yml
  Scenario: Cancel the backup schedule for an environment
    When I run "terminus backup:automatic:enable [[test_site_name]].dev --day=mon"
    Then I should get "Backup schedule successfully set."
    When I run "terminus backup:automatic:disable [[test_site_name]].dev"
    Then I should get: "Backup schedule successfully canceled."
    When I run "terminus backup:automatic:info [[test_site_name]].dev"
    Then I should not get: "Monday"
