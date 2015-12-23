Feature: List Backups for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to list my backups.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_backups_list
  Scenario: Show all backups for an environment
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --format=json"
    Then I should have "24" records
    And I should get: "code.tar.gz"

  @vcr site_backups_list
  Scenario: Show only the latest backup for an environment
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --latest --format=json"
    Then I should have "1" records
    And I should get: "database.sql.gz"

  @vcr site_backups_list
  Scenario: Filter backups by element
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --element=db --format=json"
    Then I should have "12" records
    And I should get: "database.sql.gz"
    And I should not get: "code.tar.gz"
