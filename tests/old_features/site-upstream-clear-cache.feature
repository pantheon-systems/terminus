Feature: Clearing a site's code cache
  In order to keep my site running smoothly and see new upstream changes
  As a user
  I need to be able to clear my site's code cache.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-upstream-cache-clear.yml
  Scenario: Clear the site's code cache
    When I run "terminus site:upstream:clear-cache [[test_site_name]]"
    Then I should get: "Code cache cleared on [[test_site_name]]."
