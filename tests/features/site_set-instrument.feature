Feature: Set payment instruments
  In order to pay for my site
  As a user
  I need to be able to associate a payment instrument with my site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_set-instrument_add
  Scenario: Adding instruments
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=[[payment_instrument_uuid]]"
    Then I should get:
    """
    Associated a payment method to the site
    """

  @vcr site_set-instrument_remove
  Scenario: Removing instruments
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=none"
    Then I should not get:
    """
    [[payment_instrument_uuid]]
    """
