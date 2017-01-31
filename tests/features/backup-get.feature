Feature: Get a particular backup for a site
  In order to secure my site against failures
  As a user
  I need to be able to get download information for a backup

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-get-file.yml
  Scenario: Get the URL of the latest code backup
    When I run "terminus backup:get [[test_site_name]].dev --element=code"
    Then I should get:
    """
    https://pantheon-backups.s3.amazonaws.com/11111111-1111-1111-1111-111111111111/dev/1471562180_backup/[[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz?Signature=INoN9zDlMfWa8A%2B%2BtxqdLhRI1Rs%3D&Expires=1471565930&AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ
    """

  @vcr backup-get-file.yml
  Scenario: Get the URL of a specific backup by filename
    When I run "terminus backup:get [[test_site_name]].dev --file=[[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz"
    Then I should get:
    """
    https://pantheon-backups.s3.amazonaws.com/11111111-1111-1111-1111-111111111111/dev/1471562180_backup/[[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz?Signature=INoN9zDlMfWa8A%2B%2BtxqdLhRI1Rs%3D&Expires=1471565930&AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ
    """

  @vcr backup-get-none.yml
  Scenario: Fail to find a matching backup
    When I run "terminus backup:get [[test_site_name]].test --element=database"
    Then I should get:
    """
    No backups available. Create one with `terminus backup:create [[test_site_name]].test`
    """
