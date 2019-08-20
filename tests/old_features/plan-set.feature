Feature: Set a site's service level
  In order to ensure the level of service my site requires
  As a user
  I need to be able to change the plan of my site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr plan-set.yml
  Scenario: Changing the plan of a site
    When I run "terminus plan:set [[test_site_name]] plan-free-preferred-monthly-1"
    Then I should get:
    """
    Setting plan of "[[test_site_name]]" to "plan-free-preferred-monthly-1".
    """
    And I should get: "Change site plan"

  @vcr plan-set-fail.yml
  Scenario: Attempting to change the plan to an ineligible plan
    When I run "terminus plan:set [[test_site_name]] invalid"
    Then I should get: "Could not find a plan identified by invalid."
