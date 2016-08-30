Feature: Update a site with all its upstream's updates
  In order to easily maintain my site
  As a user
  I need to be able to update my site to reflect updates in its current upstream.

  Background: I am authenticated and I have a site named [[test_site_name]] using Git mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"

  @vcr site_upstream-updates
  Scenario: Check for upstream updates
    When I run "terminus upstream:updates --site=[[test_site_name]] --print"
    Then I should get one of the following: "No updates to, Update to"

