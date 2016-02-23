Feature: Accessing the site dashboard
  In order to switch to quickly access a GUI to administer my site
  As a user
  I need to be able to get the URL of my Dashboard from Terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_dashboard
  Scenario: Printing out the Dashboard URL
    When I run "terminus site dashboard --site=[[test_site_name]] --print"
    Then I should get: "https://[[host]]/sites/"
