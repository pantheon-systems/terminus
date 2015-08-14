Feature: Site Connection Info

  Scenario: Show all connection info for a site evironment
    @vcr site_connection-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    mysql_password
    """

  Scenario: Show specific connection value
    @vcr site_connection-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site connection-info --site=[[test_site_name]] --env=dev --field=git_url"
    Then I should get:
    """
    git://
    """
    And I should not get:
    """
    sftp://
    """
