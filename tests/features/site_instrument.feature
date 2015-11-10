Feature: Set payment instruments
  In order to pay for my site
  As a user
  I need to be able to associate a payment instrument with my site.

  @vcr site_set-instrument_add
  Scenario: Adding instruments
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=[[payment_instrument_uuid]]"
    Then I should get:
    """
    Associated a payment method to the site
    """

  @vcr site_set-instrument_remove
  Scenario: Removing instruments
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=none"
    Then I should not get:
    """
    [[payment_instrument_uuid]]
    """

  @vcr site_set-instrument_forbidden
  Scenario: Denying a forbidden instrument
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-instrument --site=[[test_site_name]] --instrument=XXXXX"
    Then I should get:
    """
    You do not have permission to attach instrument XXXXX
    """
