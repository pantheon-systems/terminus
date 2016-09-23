@environment @connection
Feature: Environment Connection Info Command
  In order to see connection parameters for remotely accessing the environment of a given site
  As a Terminus user
  I want a command to view the current connection parameters.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr site_connection-info
  Scenario: Show all connection info for a site's environment
    When I run "terminus connection:info [[test_site_name]].dev"
    Then I should see a table with rows like:
    """
      SFTP Command
      Git Command
      MySQL Command
    """

  @vcr site_connection-info
  Scenario: Show only a specific connection info parameter for a site's environment
    When I run "terminus connection:info [[test_site_name]].dev --fields=git_command"
    Then I should see a table with rows like:
    """
      Git Command
    """
    And I should not get:
    """
      SFTP Command
    """

  @vcr site_connection-info
  Scenario: Show only a specific connection info parameter for a site's environment
    When I run "terminus connection:info [[test_site_name]].dev --field=git_command"
    Then I should see a table with rows like:
    """
      git clone ssh://codeserver
    """
    And I should not get:
    """
      Git Command
    """

  @vcr site_connection-info
  Scenario: Show only a specific connection info parameter for a site's environment
    When I run "terminus connection:info [[test_site_name]]"
    Then I should see an error message: The environment argument must be given as <site_name>.<environment>
