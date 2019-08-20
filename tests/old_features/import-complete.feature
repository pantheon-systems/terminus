Feature: Complete site migration
  In order to view site information
  As a user
  I need to be able to list data related to it.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-import-complete.yml
  Scenario: Complete a site migration
    When I run "terminus site:import:complete [[test_site_name]]"
    Then I should get: "The import of [[test_site_name]] has been marked as complete."
