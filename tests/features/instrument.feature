Feature: Payment Instruments

  Scenario: Adding instruments
    @vcr site-instrument-add
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And a payment instrument with uuid "[[payment_instrument_uuid]]"
    When I run "terminus site instrument --site=[[test_site_name]] --instrument=[[payment_instrument_uuid]]"
    Then I should get:
    """
    Associated a payment method to the site
    """

  Scenario: Removing instruments
    @vcr site-instrument-remove
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And a payment instrument with uuid "[[payment_instrument_uuid]]"
    When I run "terminus site instrument --site=[[test_site_name]] --instrument=none"
    Then I should not get:
    """
    [[payment_instrument_uuid]]
    """
