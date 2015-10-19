Feature: Running Drush commands

  Scenario: Running a command that is not available via Terminus
    @vcr drush_unavailable
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus drush sql-connect --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    sql-connect is not available via Terminus. Please run it via Drush, or you can use `terminus site connection-info --field=mysql_connection` to complete the same task.
    """
