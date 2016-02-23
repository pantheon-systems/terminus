Feature: Managing a site's team
  In order to work collaboratively
  As a user
  I need to be able to alter a site's team membership.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_team_add-member
  Scenario: Adding a team member
    When I run "terminus site team add-member --site=[[test_site_name]] --member=[[other_user]] --role=team_member"
    And I list the team members on "[[test_site_name]]"
    Then I should get:
    """
    [[other_user]]
    """

  @vcr site_team_change-role
  Scenario: Changing a team member's role
    When I run "terminus site team change-role --site=[[test_site_name]] --member=[[other_user]] --role=admin"
    Then I should get one of the following: "This site does not have its change-management option enabled., Changed a user role"

  @vcr site_team_list
  Scenario: Listing team members
    When I run "terminus site team list --site=[[test_site_name]]"
    Then I should get:
    """
    Role
    """

  @vcr site_team_remove-member
  Scenario: Removing a team member
    When I run "terminus site team remove-member --site=[[test_site_name]] --member=[[other_user]]"
    Then I should get:
    """
    Removed a user from site team
    """
