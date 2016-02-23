Feature: Clearing a site's cache
  In order to keep my site running smoothly and see new changes
  As a user
  I need to be able to clear my site's cache.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_clear-cache
  Scenario: Clear Caches on a Site
    When I run "terminus site clear-cache --site=[[test_site_name]] --env=dev"
    Then I should get "."
    Then I should get:
    """
    Cleared caches for "dev"
    """
