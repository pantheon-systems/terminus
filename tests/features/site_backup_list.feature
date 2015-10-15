Feature: List Backups for a Site

  Scenario: Show all backups for an environment
    @vcr site_backups_list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --format=json"
    Then I should have "8" records
    And I should get: "code.tar.gz"

  Scenario: Filter backups by element
    @vcr site_backups_filter
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=[[test_site_name]] --env=dev --element=db --format=json"
    Then I should have "2" records
    And I should get: "database.sql.gz"
    And I should not get: "code.tar.gz"
