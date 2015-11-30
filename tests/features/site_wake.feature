Feature: Waking a site

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_wake
  Scenario: Waking a site
    When I run "terminus site wake --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    OK >> dev-[[test_site_name]].pantheon.io responded in
    """
