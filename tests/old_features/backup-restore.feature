Feature: Restore a backup for a site
  In order to keep my site operating effectively
  As a user
  I need to be able to restore a backup I've created.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-restore.yml
  Scenario: Restore the latest code backup
    When I run "terminus backup:restore [[test_site_name]].dev --element=code --yes"
    Then I should get: "Restored the backup to dev."

  @vcr backup-restore.yml
  Scenario: Restore a specific backup referenced by filename
    When I run "terminus backup:restore [[test_site_name]].dev --file=[[test_site_name]]_dev_2016-10-25T19-13-37_UTC_files.tar.gz --yes"
    Then I should get: "Restored the backup to dev."
