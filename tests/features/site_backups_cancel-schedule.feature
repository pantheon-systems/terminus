Feature: Cancel a Backup Schedule for a Site
  In order to manage the security of my site content
  As a user
  I need to be able to cancel my automated backup schedule.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And I list the sites
    And a site named "[[test_site_name]]"

  @vcr site_backups_cancel-schedule
  Scenario: Cancel the backup schedule for an environment
    When I run "terminus site backups set-schedule --site=[[test_site_name]] --env=dev --day=Fri"
    And I run "terminus site backups cancel-schedule --site=[[test_site_name]] --env=dev"
    And I run "terminus site backups get-schedule --site=[[test_site_name]] --env=dev"
    Then I should not get:
    """
    Friday
    """
