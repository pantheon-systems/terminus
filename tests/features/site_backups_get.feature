Feature: List Backups for a Site
  In order to secure my site against failures
  As a user
  I need to be able to list, create, and alter backups.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_backups_get_file
  Scenario: Get the URL of the latest DB backup
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=code --latest"
    Then I should get:
    """
    https://onebox-pantheon-backups.s3.amazonaws.com/aa2a29d7-dc84-42a8-9ed0-c5bc1b725c61/dev/1456526466_backup/behat-tests_dev_2016-02-26T22-41-06_UTC_code.tar.gz?Signature=G8%2BSvwSaNDwfl%2FyrJFAvTdBKQvs%3D&Expires=1456528484&AWSAccessKeyId=AKIAIYWQRFTHOPSVWJ2A
    """

  @vcr site_backups_get_file
  Scenario: Get the URL of a specific backup
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --file=behat-tests_dev_2016-02-26T22-41-06_UTC_code.tar.gz"
    Then I should get:
    """
    https://onebox-pantheon-backups.s3.amazonaws.com/aa2a29d7-dc84-42a8-9ed0-c5bc1b725c61/dev/1456526466_backup/behat-tests_dev_2016-02-26T22-41-06_UTC_code.tar.gz?Signature=G8%2BSvwSaNDwfl%2FyrJFAvTdBKQvs%3D&Expires=1456528484&AWSAccessKeyId=AKIAIYWQRFTHOPSVWJ2A
    """

  @vcr site_backups_get_invalid
  Scenario: Fail to get the URL of the latest backup of an invalid element
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=invalid --latest"
    Then I should get: "invalid is an invalid element. Please select one of the following: code, database, files"

  @vcr site_backups_get_file_invalid
  Scenario: Fail to get the URL of a specific backup
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --file=invalid"
    Then I should get: "Cannot find a backup named invalid."

  @vcr site_backups_get_none
  Scenario: Fail to get backups where there are none
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=database --latest"
    Then I should get:
    """
    No backups available. Please create one with `terminus site backups create --site=[[test_site_name]] --env=dev`
    """
