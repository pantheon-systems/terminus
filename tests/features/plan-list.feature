Feature: Listing available plans
  In order to decide which plan I can use
  As a user
  I need to be able to list available site plans.

  Background: I am logged in
    Given I am authenticated

  @vcr plan-list.yml
  Scenario: List plans available for my site
    When I run "terminus plan:list [[test_site_name]]"
    Then I should see a table with rows like:
    """
      SKU
      Name
      Billing Cycle
      Price
      Monthly Price
    """
