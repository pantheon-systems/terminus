Feature: Getting an environment's url
  In order to view my site
  As a user
  I need to be able to find the URL of my site's environments.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-view.yml
  Scenario: Getting the url for an environment
    When I run "terminus env:view [[test_site_name]].dev --print"
    Then I should get:
    """
    http://dev-behat-tests.onebox.pantheonsite.io/
    """

