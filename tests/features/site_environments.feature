Feature: Listing a site's environments
  In order to administer my site
  As a user
  I need to be able to list all of its environments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_environments
  Scenario: Listing all environments belonging to a site
    When I run "terminus site environments --site=[[test_site_name]]"
    Then I should get: "OnServer Dev?"
