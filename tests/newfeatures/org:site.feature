Feature: Organization sites
  In order to associate sites with organizations
  As an organizational user
  I need to be able to list and edit organizational site memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_sites_list
  Scenario: List an organization's sites
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:list [[organization_name]]"
    Then I should get: "[[test_site_name]]"
    And I should not get: "PHP Notice"

  @vcr organizations_sites_add
  Scenario: Add a site to an organization
    Given a site named "[[test_site_name]]"
    When I run "terminus org:site:add --org=[[organization_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Added a site to the organization
    """

  @vcr organizations_sites_remove
  Scenario: Remove a site from an organization
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:remove --org=[[organization_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Removed a site to the organization
    """
