Feature: Listing one's organizational memberships
  In order to manage my organizational memberships
  As an organizational user
  I need to be able to list my organizational memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_list
  Scenario: List a user's organizational memberships
    When I run "terminus organizations list"
    Then I should get: "Name	Id"
    And I should get: "AnotherOrg	c44e5de1-77b5-4151-b89f-9f548c5d909e"
    And I should get: "[[organization_name]]	11111111-1111-1111-1111-111111111111"
