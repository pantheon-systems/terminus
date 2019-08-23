Feature: Get a particular backup for a site
  In order to secure my site against failures
  As a user
  I need to be able to get information about a backup that has been made

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-get-file.yml
  Scenario: Gets information about the latest code backup made
    When I run "terminus backup:info [[test_site_name]].dev --element=code"
    Then I should get: "----------- -----------------------------------------------------"
    And I should get: "Filename    [[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz"
    And I should get: "Size        31.8MB"
    And I should get: "Date        2016-08-18 23:16:30"
    And I should get: "Expiry      2017-08-19 05:02:06"
    And I should get: "Initiator   manual"
    And I should get: "----------- -----------------------------------------------------"

  @vcr backup-get-file.yml
  Scenario: Gets informtion about a backup selected by filename
    When I run "terminus backup:info [[test_site_name]].dev --file=[[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz"
    Then I should get: "----------- -----------------------------------------------------"
    And I should get: "Filename    [[test_site_name]]_dev_2016-08-18T23-16-20_UTC_code.tar.gz"
    And I should get: "Size        31.8MB"
    And I should get: "Date        2016-08-18 23:16:30"
    And I should get: "Expiry      2017-08-19 05:02:06"
    And I should get: "Initiator   manual"
    And I should get: "----------- -----------------------------------------------------"

  @vcr backup-get-none.yml
  Scenario: Failing to find a matching backup
    When I run "terminus backup:info [[test_site_name]].test --element=database"
    Then I should get:
    """
    No backups available. Create one with `terminus backup:create [[test_site_name]].test`
    """
