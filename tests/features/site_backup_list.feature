Feature: List Backups for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to list my backups.

  @vcr site_backups_list
  Scenario: Show all backups for an environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --format=json"
    Then I should have "8" records
    And I should get: "code.tar.gz"

  @vcr site_backups_filter
  Scenario: Filter backups by element
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --element=db --format=json"
    Then I should have "2" records
    And I should get: "database.sql.gz"
    And I should not get: "code.tar.gz"
