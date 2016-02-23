Feature: Listing one's organizational memberships
  In order to manage my organizational memberships
  As an organizational user
  I need to be able to list my organizational memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_list
  Scenario: List a user's organizational memberships
    When I run "terminus organizations list"
    Then I should get:
    """
    [[enterprise_org_name]]
    """
