Feature: Organization sites
  In order to associate sites with organizations
  As an organizational user
  I need to be able to list and edit organizational site memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr org-site-list.yml
  Scenario: List an organization's sites
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:list '[[organization_name]]'"
    Then I should see a table with rows like:
    """
        Name
        ID
        Created
        Service Level
        Framework
        Owner
        Created
        Tags
    """

  @vcr org-site-list.yml
  Scenario: List an organization's sites, filtered by tag
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:list '[[organization_name]]' --tag=tag"
    And I should see a table with rows like:
    """
        Name
        ID
        Created
        Service Level
        Framework
        Owner
        Created
        Tags
    """

  @vcr organization-site-remove.yml
  Scenario: Remove a site from an organization
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:remove '[[organization_name]]' [[test_site_name]]"
    Then I should get: "[[test_site_name]] has been removed from the [[organization_name]] organization."
