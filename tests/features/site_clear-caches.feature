Feature: site clear-caches

  Scenario: Clear Caches on a Site
    @vcr site_clear-caches
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site clear-caches --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Caches cleared
    """
