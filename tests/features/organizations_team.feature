Feature: Organizational users
  In order to coordinate users within organizations
  As an organizational user
  I need to be able to list organizational user memberships.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated

  @vcr organizations_team_list
  Scenario: List an organization's teammates
    When I run "terminus organizations team --org='[[enterprise_org_name]]'"
    Then I should get:
    """
    [[username]]
    """

  @vcr organizations_team_list_invalid
  Scenario: Fail to list an invalid organization's teammates
    When I run "terminus organizations team --org=invalid"
    Then I should get:
    """
    The organization invalid is either invalid or you haven't permission sufficient to access its data.
    """
