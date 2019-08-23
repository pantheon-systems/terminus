Feature: Listing one's organizational memberships
  In order to manage my organizational memberships
  As an organizational user
  I need to be able to list my organizational memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr org-list.yml
  Scenario: List a user's organizational memberships
    When I run "terminus org:list"
    Then I should get: "-------------------------------------- --------------- -------------------"
    And I should get: "ID                                     Name            Label"
    And I should get: "-------------------------------------- --------------- -------------------"
    And I should get: "c44e5de1-77b5-4151-b89f-9f548c5d909e   anotherorg      AnotherOrg"
    And I should get: "11111111-1111-1111-1111-111111111111   enterpriseorg   Organization Name"
    And I should get: "-------------------------------------- --------------- -------------------"

  @vcr org-list-empty.yml
  Scenario: List a user's organizational memberships when there aren't any
    When I run "terminus org:list"
    Then I should get the warning: "You are not a member of any organizations."
