@environment @connection
Feature: Environment Connection Info Command
  In order to see connection parameters for remotely accessing the environment of a given site
  As a Terminus user
  I want a command to view the current connection parameters.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr connection-info.yml
  Scenario: Show the default connection info for a site environment
    When I run "terminus connection:info [[test_site_name]].dev"
    Then I should see a table with rows like:
    """
      SFTP Command
      Git Command
      MySQL Command
      Redis Command
    """
    And I should not get:
    """
      Git URL
    """

  @vcr connection-info.yml
  Scenario: Show connection info for a site environment using a qualified field glob
    When I run "terminus connection:info [[test_site_name]].dev --fields='*_url'"
    Then I should see a table with rows like:
    """
      SFTP URL
      Git URL
      MySQL URL
    """
    And I should not get:
    """
      Git Command
    """

  @vcr connection-info.yml
  Scenario: Show all connection info for a site environment using a field glob
    When I run "terminus connection:info [[test_site_name]].dev --fields='*'"
    Then I should see a table with rows like:
    """
      SFTP Command
      SFTP Username
      SFTP Host
      SFTP Password
      SFTP URL
      Git Command
      Git Username
      Git Host
      Git Port
      Git URL
      MySQL Command
      MySQL Username
      MySQL Host
      MySQL Password
      MySQL URL
      MySQL Port
      MySQL Database
      Redis Command
      Redis Port
      Redis URL
      Redis Password
    """

  @vcr connection-info.yml
  Scenario: Show only a specific connection info parameter for a site environment
    When I run "terminus connection:info [[test_site_name]].dev --fields=git_command"
    Then I should see a table with rows like:
    """
      Git Command
    """
    And I should not get:
    """
      SFTP Command
    """

  @vcr connection-info.yml
  Scenario: Show only a specific connection info parameter using a field label
    When I run "terminus connection:info [[test_site_name]].dev --fields='Git Command'"
    Then I should see a table with rows like:
    """
      Git Command
    """
    And I should not get:
    """
      SFTP Command
    """

  @vcr connection-info.yml
  Scenario: Show only a specific connection info parameter using a single field key
    When I run "terminus connection:info [[test_site_name]].dev --field=git_command"
    Then I should see a table with rows like:
    """
      git clone ssh://codeserver
    """
    And I should not get:
    """
      Git Command
    """

  @vcr connection-info.yml
  Scenario: Show an error if the environment is not correctly specified
    When I run "terminus connection:info [[test_site_name]]"
    Then I should see an error message: The environment argument must be given as <site_name>.<environment>
