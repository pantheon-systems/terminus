Feature: Displaying environmental information
  In order to access and work with the Pantheon platform
  As a user
  I need to be able to check information on my site's environments.

  @vcr site_environment-info
  Scenario: Checking environmental information
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    dev
    """

  @vcr site_environment-info
  Scenario: Checking an information field of an environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  @vcr site_environment-info
  Scenario: Failing to check an invalid field
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev --field=invalid"
    Then I should get:
    """
    There is no such field.
    """

