Feature: List Backups for a Site
  In order to secure my site against failures
  As a user
  I need to be able to list, create, and alter backups.

  @vcr site_backups_get_latest
  Scenario: Get the URL of the latest DB backup
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=db --latest"
    Then I should get: "https://onebox-pantheon-backups.s3.amazonaws.com/d75eacdd-20d4-40d0-8178-74e4aed0dffc/dev/1444938388_backup/behat-tests_dev_2015-10-15T19-46-28_UTC_database.sql.gz?Signature=kVefOYFJzeDDmGYofUd1THg8XNo%3D&Expires=1445907646&AWSAccessKeyId=AKIAIYWQRFTHOPSVWJ2A"

  @vcr site_backups_get_file
  Scenario: Get the URL of a specific backup
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --file=behat-tests_dev_2015-10-15T19-46-28_UTC_database.sql.gz"
    Then I should get: "https://onebox-pantheon-backups.s3.amazonaws.com/d75eacdd-20d4-40d0-8178-74e4aed0dffc/dev/1444938388_backup/behat-tests_dev_2015-10-15T19-46-28_UTC_database.sql.gz?Signature=4Rp8YChvk%2Bbgm%2FCtM501mGEZ%2Fyo%3D&Expires=1445907654&AWSAccessKeyId=AKIAIYWQRFTHOPSVWJ2A"

  @vcr site_backups_get_invalid
  Scenario: Fail to get the URL of the latest backup of an invalid element
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --element=invalid --latest"
    Then I should get: "invalid is an invalid element. Please select one of the following: code, database, files"

  @vcr site_backups_get_file_invalid
  Scenario: Fail to get the URL of a specific backup
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups get --site=[[test_site_name]] --env=dev --file=invalid"
    Then I should get: "Cannot find a backup named invalid."
