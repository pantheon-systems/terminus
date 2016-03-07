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
    When I run "terminus site upstream-updates list --site=[[test_site_name]]"
    Then I should get one of the following: "No updates to, Update to"

  @vcr site_upstream-updates
  Scenario: Apply upstream updates
    When I run "terminus site upstream-updates apply --site=[[test_site_name]] --yes"
    Then I should get one of the following: "Updates applied, Apply upstream updates"
