Feature: Payment method command
  In order to pay for service
  As a user
  I need to be able to view and use my payment instruments.

  Background: I am logged in
    Given I am authenticated

  @vcr instruments_list
  Scenario: Listing a user's payment methods
    When I run "terminus payment-method:list"
    Then I should get: "[[instrument_label]]"

  @vcr site_set-instrument_add
  Scenario: Adding payment methods
    Given a site named "[[test_site_name]]"
    When I run "terminus payment-method:set [[instrument_uuid]] --site=[[test_site_name]]"
    Then I should get:
    """
    Associated a payment method to the site
    """

  @vcr site_set-instrument_remove
  Scenario: Removing payment methods
    Given a site named "[[test_site_name]]"
    When I run "terminus payment-method:remove --site=[[test_site_name]]"
    Then I should not get:
    """
    [[payment_instrument_uuid]]
    """
