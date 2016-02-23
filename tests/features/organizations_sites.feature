Feature: Organization sites
  In order to associate sites with organizations
  As an organizational user
  I need to be able to list and edit organizational site memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_sites_list
  Scenario: List an organization's sites
    Given a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites list --org=[[enterprise_org_name]]"
    Then I should get: "[[test_site_name]]"
    And I should not get: "PHP Notice"

  @vcr organizations_sites_add
  Scenario: Add a site to an organization
    Given a site named "[[test_site_name]]"
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Added a site to the organization
    """

  @vcr organizations_sites_add_invalid
  Scenario: Fail to add an invalid site to an organization
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=invalid --yes"
    Then I should get:
    """
    Cannot find site with the name "invalid"
    """

  @vcr organizations_sites_add_duplicate
  Scenario: Fail to add a duplicate member site to an organization
    Given a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    [[test_site_name]] is already a member of [[enterprise_org_name]]
    """

  @vcr organizations_sites_remove
  Scenario: Remove a site from an organization
    Given a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Removed a site to the organization
    """

  @vcr organizations_sites_remove_invalid
  Scenario: Fail to remove an invalid site from an organization
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=invalid --yes"
    Then I should get:
    """
    invalid is not a member of [[enterprise_org_name]]
    """

  @vcr organizations_sites_remove_duplicate
  Scenario: Fail to remove a non-member site from an organization
    Given a site named "[[test_site_name]]"
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    [[test_site_name]] is not a member of [[enterprise_org_name]]
    """
