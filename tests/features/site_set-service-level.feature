Feature: Service level

  Scenario: Changing the service level
    @vcr site_set-service-level
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-service-level --site=[[test_site_name]] --level=pro"
    Then I should get:
    """
    Service level has been updated to 'pro'
    """

  Scenario: Changing service level without payment method
    @vcr site_set-service-level_fail
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-service-level --site=[[test_site_name]] --level=pro"
    Then I should get:
    """
    Instrument required to increase service level
    """

  Scenario: Changing to incorrect service level
    @vcr site_set-service-level_wrong
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-service-level --site=[[test_site_name]] --level=professional"
    Then I should get:
    """
    Service level "professional" is invalid.
    """
