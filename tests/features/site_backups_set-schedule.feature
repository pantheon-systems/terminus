Feature: Set a Backup Schedule for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to set my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And I list the sites
    And a site named "[[test_site_name]]"

  @vcr site_backups_set-schedule
  Scenario: Set the backup schedule for an environment
    When I run "terminus site backups set-schedule --site=[[test_site_name]] --env=dev --day=Fri"
    And I run "terminus site backups get-schedule --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Friday
    """
