Feature: Listing commits on an environment
  In order to maintain my site
  As a user
  In need to be able to list the commits on a branch

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_code_log
  Scenario: Listing the commit log of a site
    When I run "terminus site code log --site=[[test_site_name]] --env=dev"
    Then I should get: "Initial Commit"
