Feature: site clear-cache

  Scenario: Clear Caches on a Site
    @vcr site_clear-cache
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site clear-cache --site=[[test_site_name]] --env=dev"
    Then I should get "."
    Then I should get:
    """
    Cleared caches for "dev"
    """
