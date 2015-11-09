Feature: Display site connection information
  In order to see whether the site connection type must be altered before changes
  As a user
  I need to be able to check the current connection mode.

  @vcr site_connection-info
  Scenario: Show all connection info for a site evironment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Sftp Password
    """

  @vcr site_connection-info
  Scenario: Show specific connection value
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev --field=git_url"
    Then I should get:
    """
    ssh://
    """
    And I should not get:
    """
    sftp://
    """
