Feature: Running Drush commands
  In order to use Drush
  As a user with a Drupal site
  I need to be able to send commands to Pantheon through Drush.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr drush_unavailable
  Scenario: Running a command that is not available via Terminus
    When I run "terminus drush 'sql-connect' --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    sql-connect is not available via Terminus. Please run it via Drush, or you can use `terminus site connection-info --field=mysql_connection` to complete the same task.
    """
