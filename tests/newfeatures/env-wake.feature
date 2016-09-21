Feature: Waking a site

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_wake
  Scenario: Waking a site
    When I run "terminus env:wake dev --site=[[test_site_name]]"
    Then I should get:
    """
    OK >> dev-[[test_site_name]].[[php_site_domain]] responded in
    """
