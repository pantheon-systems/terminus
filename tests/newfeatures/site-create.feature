Feature: Create a site
  In order to use the Pantheon platform
  As a user
  I need to be able to create a site on it.

  Background: I am authenticated
    Given I am authenticated

  @vcr site_create
  Scenario: Create Site
    When I run "terminus site:create [[test_site_name]] --label=[[test_site_name]] --upstream=WordPress"
    Then I should get: "Creating new site installation ..."
    Then I should get:
    """
    Creating new site "[[test_site_name]]"
    """
