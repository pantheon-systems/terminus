Feature: Display site connection information
  In order to see whether the site connection type must be altered before changes
  As a user
  I need to be able to check the current connection mode.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_connection-info
  Scenario: Show all connection info for a site evironment
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Sftp Password
    """

  @vcr site_connection-info
  Scenario: Show specific connection value
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev --field=git_url"
    Then I should get:
    """
    ssh://
    """
    And I should not get:
    """
    sftp://
    """
