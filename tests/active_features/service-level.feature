Feature: Set a site's service level
  In order to ensure the level of service my site requires
  As a user
  I need to be able to change the service level on my site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr service_level_set.yml
  Scenario: Changing the service level
    When I run "terminus service-level:set pro [[test_site_name]]"
    Then I should get:
    """
    Changing site plan to "pro"
    """

  @vcr service_level_set_fail.yml
  Scenario: Changing service level without payment method
    When I run "terminus service-level:set pro [[test_site_name]]"
    Then I should get:
    """
    needs to be paid for before the service level can be changed
    """
