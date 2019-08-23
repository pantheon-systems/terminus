Feature: Waking a site

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-wake.yml
  Scenario: Waking a site
    When I run "terminus env:wake [[test_site_name]].dev"
    Then I should get:
    """
    OK >> dev-[[test_site_name]].[[php_site_domain]] responded
    """
