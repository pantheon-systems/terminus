Feature: Payment method command
  In order to pay for service
  As a user
  I need to be able to view and use my payment methods.

  Background: I am logged in
    Given I am authenticated

  @vcr instruments_list
  Scenario: Listing a user's payment methods
    When I run "terminus payment-method:list"
    Then I should get: "------------- --------------------------------------"
    And I should get: "Label         ID"
    And I should get: "------------- --------------------------------------"
    And I should get: "[[payment_method_label]]   8558e04f-3674-481e-b448-bccff73cb430"
    And I should get: "------------- --------------------------------------"

  @vcr payment-methods_list_empty.yml
  Scenario: Listing a user's payment methods when they don't have any
    When I run "terminus payment-method:list"
    Then I should get: "There are no payment methods attached to this account."
    And I should get: "------- ----"
    And I should get: "Label   ID"
    And I should get: "------- ----"

  @vcr site_set-instrument_add
  Scenario: Adding payment methods
    Given a site named "[[test_site_name]]"
    When I run "terminus payment-method:add [[test_site_name]] '[[payment_method_label]]'"
    Then I should get: "[[payment_method_label]] has been applied to the [[test_site_name]] site."

  @vcr site_set-instrument_remove
  Scenario: Removing payment methods
    Given a site named "[[test_site_name]]"
    When I run "terminus payment-method:remove [[test_site_name]]"
    Then I should get: "The payment method for the [[test_site_name]] site has been removed."
