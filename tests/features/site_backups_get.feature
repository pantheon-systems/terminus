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
    https://pantheon-backups.s3.amazonaws.com/11111111-1111-1111-1111-111111111111/dev/1471562180_backup/behat-tests_dev_2016-08-18T23-16-20_UTC_code.tar.gz?Signature=INoN9zDlMfWa8A%2B%2BtxqdLhRI1Rs%3D&Expires=1471565930&AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ
    """

  @vcr site_backups_get_file
  Scenario: Get the URL of a specific backup
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --file=behat-tests_dev_2016-08-18T23-16-20_UTC_code.tar.gz"
    Then I should get:
    """
    https://pantheon-backups.s3.amazonaws.com/11111111-1111-1111-1111-111111111111/dev/1471562180_backup/behat-tests_dev_2016-08-18T23-16-20_UTC_code.tar.gz?Signature=INoN9zDlMfWa8A%2B%2BtxqdLhRI1Rs%3D&Expires=1471565930&AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ
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
    When I run "terminus site backups get --site=[[test_site_name]] --env=test --element=database --latest"
    Then I should get:
    """
    No backups available. Please create one with `terminus site backups create --site=[[test_site_name]] --env=test`
    """
