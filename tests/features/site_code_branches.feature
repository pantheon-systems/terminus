Feature: Listing site branches
  In order to maintain my git repository for my site
  As a user
  In need to be able to list extant branches

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_code_branches
  Scenario: Listing the branches of a site
    When I run "terminus site code branches --site=[[test_site_name]]"
    Then I should get: "Title"
