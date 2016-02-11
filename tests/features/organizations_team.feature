Feature: Organizational users
  In order to coordinate users within organizations
  As an organizational user
  I need to be able to list organizational user memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_team_list
  Scenario: List an organization's teammates
    When I run "terminus organizations team list --org='[[enterprise_org_name]]'"
    Then I should get:
    """
    [[username]]
    """

  @vcr organizations_team_list_invalid
  Scenario: Fail to list an invalid organization's teammates
    When I run "terminus organizations team list --org=invalid"
    Then I should get:
    """
    The organization invalid is either invalid or you haven't permission sufficient to access its data.
    """

  @vcr organizations_team_add-member
  Scenario: Add a new member to a team
    When I run "terminus organizations team add-member --org=[[enterprise_org_name]] --member=[[other_user]] --role=team_member"
    Then I should get:
    """
    Added "[[other_user]]" to the organization
    """

  @vcr organizations_team_add-member_duplicate
  Scenario: Failing to add a member to a team again
    When I run "terminus organizations team add-member --org=[[enterprise_org_name]] --member=[[other_user]] --role=team_member"
    Then I should get:
    """
    Membership already exists. Try updating it instead.
    """

  @vcr organizations_team_remove-member
  Scenario: Removing a new member from a team
    When I run "terminus organizations team remove-member --org=[[enterprise_org_name]] --member=[[other_user]]"
    Then I should get:
    """
    Removed user from the organization
    """

  @vcr organizations_team_remove-member_invalid
  Scenario: Failing to remove a user who is not a member from a team
    When I run "terminus organizations team remove-member --org=[[enterprise_org_name]] --member=invalid"
    Then I should get:
    """
    An organization member idenfitied by "invalid" could not be found.
    """

  @vcr organizations_team_change-role
  Scenario: Changing a team member's role
    When I run "terminus organizations team change-role --org=[[enterprise_org_name]] --member=[[other_user]] --role=developer"
    Then I should get:
    """
    Updated role to "developer"
    """

  @vcr organizations_team_remove-member_invalid
  Scenario: Changing a team member's role
    When I run "terminus organizations team change-role --org=[[enterprise_org_name]] --member=invalid --role=developer"
    Then I should get:
    """
    An organization member idenfitied by "invalid" could not be found.
    """
