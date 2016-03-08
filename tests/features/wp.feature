Feature: Running WP-CLI commands
  In order to use WP-CLI
  As a user with a WordPress site
  I need to be able to send commands to Pantheon through WP-CLI.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr wp_unavailable
  Scenario: Running a command that is not available via Terminus
    When I run "terminus wp 'db' --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    db is not available via Terminus. Please run it via WP-CLI.
    """
