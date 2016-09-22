Feature: Viewing information about a site's upstream
  In order to easily maintain my site
  As a user
  I need to be able to view information about my site's upstream.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_upstream-info
  Scenario: Check a site's upstream information
    When I run "terminus site upstream-info --site=[[test_site_name]]"
    Then I should get one of the following: "Product Id"
