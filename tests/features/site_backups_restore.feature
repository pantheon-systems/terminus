Feature: Restore a site's backup
  In order to restore my site to a previous version
  As a user
  I need to be able to restore backups.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_backups_restore
  Scenario: Restore the latest database backup
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=db --latest"
    Then I should get:
    """
    Restored database in "dev"
    """
