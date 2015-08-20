Feature: List Backups for a Site

  Scenario: Show all backups for an environment
    @vcr site_backups_list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=behat-test --env=dev"
    Then I should get:
    """
    code.tar.gz
    """

  Scenario: Filter backups by element
    @vcr site_backups_list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site backups list --site=behat-test --env=dev --element=db"
    Then I should get:
    """
    database.sql.gz
    """
    And I should not get:
    """
    code.tar.gz
    """
