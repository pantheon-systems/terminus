Feature: Instruments command

  Scenario: List instruments
    @vcr instruments_list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus instruments list"
    Then I should get:
    """
    [[payment_instrument_uuid]]
    """
