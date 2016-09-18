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
    Then I should see a table with the headers: Parameter, Connection Info
    And I should see a table with rows like:
    """
      sftp_command
      sftp_host
      sftp_password
      sftp_url
      sftp_username
      git_username
      git_host
      git_port
      git_url
      git_command
      mysql_host
      mysql_username
      mysql_password
      mysql_port
      mysql_database
      mysql_url
      mysql_command
      redis_password
      redis_host
      redis_port
      redis_url
      redis_command
    """

  @vcr site_connection-info
  Scenario: Show only a specific connection info parameter for a site's environment
    When I run "terminus connection:info [[test_site_name]].dev git_command"
    Then I should see a table with the headers: Parameter, Connection Info
    And I should see a table with rows like:
    """
      git_command
    """

  @vcr site_connection-info
  Scenario: Specify connection info table headers
    When I run "terminus connection:info [[test_site_name]].dev --fields=env,param,value"
    Then I should see a table with the headers: Environment, Parameter, Connection Info

  @vcr site_connection-info
  Scenario: Show only a specific connection info parameter for a site's environment
    When I run "terminus connection:info [[test_site_name]]"
    Then I should see an error message: The environment argument must be given as <site_name>.<environment>
