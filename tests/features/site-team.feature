Feature: Managing a site's team
  In order to work collaboratively
  As a user
  I need to be able to alter a site's team membership.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-team-add.yml
  Scenario: Adding a team member
    When I run "terminus site:team:add [[test_site_name]] [[other_user]] --role=team_member"
    And I list the team members on "[[test_site_name]]"
    Then I should get:
    """
    [[other_user]]
    """

  @vcr site-team-role.yml
  Scenario: Changing a team member's role
    When I run "terminus site:team:role [[test_site_name]] [[other_user]] admin"
    Then I should get one of the following: "This site does not have its change-management option enabled., Changed a user role"

  @vcr site-team-list.yml
  Scenario: Listing team members
    When I run "terminus site:team:list [[test_site_name]]"
    Then I should get:
    """
    team_member
    """

  @vcr site-team-remove.yml
  Scenario: Removing a team member
    When I run "terminus site:team:remove [[test_site_name]] [[other_user]]"
    Then I should get:
    """
    Removed a user from site team
    """
