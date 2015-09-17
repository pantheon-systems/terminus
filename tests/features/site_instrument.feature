Feature: Payment Instruments

  Scenario: Adding instruments
    @vcr site_set-instrument_add
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And a payment instrument with uuid "[[payment_instrument_uuid]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=[[payment_instrument_uuid]]"
    Then I should get:
    """
    Associated a payment method to the site
    """

  Scenario: Removing instruments
    @vcr site_set-instrument_remove
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And a payment instrument with uuid "[[payment_instrument_uuid]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=none"
    Then I should not get:
    """
    [[payment_instrument_uuid]]
    """

  Scenario: Denying a forbidden instrument
    @vcr site_set-instrument_forbidden
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=XXXXX"
    Then I should get:
    """
    You do not have permission to attach instrument XXXXX
    """
