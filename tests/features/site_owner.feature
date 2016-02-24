Feature: View the UUID of the site's owner
  In order to manage a site
  As a user
  I need to be able to see who owns it

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_owner
  Scenario: Showing the site owner
    When I run "terminus site owner --site=[[test_site_name]]"
    Then I should get a valid UUID
