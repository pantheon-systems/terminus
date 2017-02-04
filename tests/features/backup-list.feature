Feature: List Backups for a Site
  In order to ensure the security of my site content
  As a user
  I need to be able to list my backups.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-list.yml
  Scenario: Show all backups for an environment
    When I run "terminus backup:list [[test_site_name]].dev --format=json"
    Then I should have "7" records
    And I should get: "code.tar.gz"

  @vcr backup-list.yml
  Scenario: Filter backups by element
    When I run "terminus backup:list [[test_site_name]].dev --element=db --format=json"
    Then I should have "2" records
    And I should get: "database.sql.gz"
    And I should not get: "code.tar.gz"
