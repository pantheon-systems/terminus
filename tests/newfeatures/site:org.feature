Feature: Managing site organizational memberships
  In order to manage what organizations a site is a member of
  As a user
  I need to be able to add and remove those relationships.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_organizations_add
  Scenario: Adding a supporting organization to a site
    When I run "terminus site:org:add --site=[[test_site_name]] --org=[[organization_name]]"
    Then I should get:
    """
    as a supporting organization
    """

  @vcr site_organizations_remove
  Scenario: Removing a supporting organization from a site
    When I run "terminus site:org:remove --site=[[test_site_name]] --org=[[organization_name]]"
    Then I should get:
    """
    Removed supporting organization
    """

  @vcr site_organizations_list
  Scenario: Listing the supporting organizations of a site
    When I run "terminus site:org:list --site=[[test_site_name]]"
    Then I should get one of the following: "[[organization_name]], No organizations"
