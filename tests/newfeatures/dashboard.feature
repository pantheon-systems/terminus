Feature: Accessing the Dashboard
  In order script the opening of Dashboard pages
  As a user
  I need to be able to get the URL of my Dashboard from Terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_dashboard
  Scenario: Printing out the site Dashboard URL
    When I run "terminus dashboard:view --site=[[test_site_name]] --print"
    Then I should get: "https://[[host]]/sites/11111111-1111-1111-1111111111111111#dev/code"
  
  @vcr site_dashboard
  Scenario: Printing out the main Dashboard URL
    When I run "terminus dashboard:view --print"
    Then I should get: "https://[[host]]/users/[[user_id]]#sites/list"
  
  @vcr site_dashboard
  Scenario: Printing out the site Dashboard URL for a specific environment
    When I run "terminus dashboard:view --site=[[test_site_name]] --env=multidev --print"
    Then I should get: "https://[[host]]/sites/11111111-1111-1111-1111111111111111#multidev/code"
  
  Scenario: Opening a Dashboard window automatically
    # We cannot test for it, but `terminus dashboard:view ...` without `--print`
    # should open the Dashboard at the URL it generates.
