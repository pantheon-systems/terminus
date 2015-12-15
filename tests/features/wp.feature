Feature: Running WP-CLI commands
  In order to use WP-CLI
  As a user with a WordPress site
  I need to be able to send commands to Pantheon through WP-CLI.

  @vcr wp_unavailable
  Scenario: Running a command that is not available via Terminus
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus wp 'import' --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    import is not available via Terminus. Please run it via WP-CLI.
    """
