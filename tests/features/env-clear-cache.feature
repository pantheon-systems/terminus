Feature: Clearing a site's cache
  In order to keep my site running smoothly and see new changes
  As a user
  I need to be able to clear my site's cache.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-cache-clear.yml
  Scenario: Clear the dev environment's cache
    When I run "terminus env:clear-cache [[test_site_name]].dev"
    Then I should get: "Caches cleared on [[test_site_name]].dev."
