Feature: Displaying environmental information
  In order to access and work with the Pantheon platform
  As a user
  I need to be able to check information on my site's environments.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_environment-info
  Scenario: Checking environmental information
    When I run "terminus env:info dev --site=[[test_site_name]]"
    Then I should get:
    """
    dev
    """

  @vcr site_environment-info
  Scenario: Checking an information field of an environment
    When I run "terminus env:info dev --site=[[test_site_name]] --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  @vcr site_environment-info
  Scenario: Failing to check an invalid field
    When I run "terminus env:info dev --site=[[test_site_name]] --field=invalid"
    Then I should get:
    """
    There is no field invalid.
    """

  @vcr site_drush-version
  Scenario: Retrieving the environment's Drush version
    When I run "terminus env:info dev --site=[[test_site_name]] --field=drush_version"
    Then I should get one of the following: "5, 6, 7, 8"
