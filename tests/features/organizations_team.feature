Feature: Organizational users
  In order to coordinate users within organizations
  As an organizational user
  I need to be able to list organizational user memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_team_list
  Scenario: List an organization's teammates
    When I run "terminus organizations team list --org='[[organization_name]]'"
    Then I should get:
    """
    [[username]]
    """

  @vcr organizations_team_add-member
  Scenario: Add a new member to a team
    When I run "terminus organizations team add-member --org='[[organization_name]]' --member=[[other_user]] --role=team_member"
    Then I should get:
    """
    Added "[[other_user]]" to the organization
    """

  @vcr organizations_team_remove-member
  Scenario: Removing a new member from a team
    When I run "terminus organizations team remove-member --org='[[organization_name]]' --member=[[other_user]]"
    Then I should get:
    """
    Removed user from the organization
    """

  @vcr organizations_team_change-role
  Scenario: Changing a team member's role
    When I run "terminus organizations team change-role --org='[[organization_name]]' --member=[[other_user]] --role=developer"
    Then I should get:
    """
    Updated role to "developer"
    """
