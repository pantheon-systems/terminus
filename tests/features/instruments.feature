Feature: Instruments command
  In order to pay for service
  As a user
  I need to be able to view and use my payment instruments.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr instruments_list
  Scenario: List instruments
    When I run "terminus instruments list"
    Then I should get:
    """
    [[payment_instrument_uuid]]
    """
