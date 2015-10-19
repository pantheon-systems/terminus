Feature: Running WP-CLI commands

  Scenario: Running a command that is not available via Terminus
    @vcr wp_unavailable
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus wp import --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    import is not available via Terminus. Please run it via WP-CLI.
    """
