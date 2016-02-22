Feature: Managing site organizational memberships
  In order to manage what organizations a site is a member of
  As a user
  I need to be able to add and remove those relationships.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_organizations_add
  Scenario: Adding a supporting organization to a site
    When I run "terminus site organizations add --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Added "[[enterprise_org_name]]" as a supporting organization
    """

  @vcr site_organizations_add_invalid
  Scenario: Failing to add an invalid organization
    When I run "terminus site organizations add --site=[[test_site_name]] --org=badorgname"
    Then I should get:
    """
    Organization is either invalid or you are not a member.
    """

  @vcr site_organizations_add_duplicate
  Scenario: Failing to add an organizaiton which is already a member
    When I run "terminus site organizations add --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Membership already exists. Try updating it instead.
    """

  @vcr site_organizations_remove
  Scenario: Removing a supporting organization from a site
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Removed supporting organization
    """

  @vcr site_organizations_remove_invalid
  Scenario: Failing to remove an invalid organization
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=badorgname"
    Then I should get:
    """
    Organization is either invalid or you are not a member.
    """

  @vcr site_organizations_remove_invalid
  Scenario: Failing to remove an organization which is not a member
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Could not find siteorganizationmembership "[[enterprise_org_uuid]]"
    """
