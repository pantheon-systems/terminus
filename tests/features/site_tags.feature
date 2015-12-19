Feature: Tagging organizational sites
  In order to organize and categorize sites
  As a user
  I need to be able to apply tags to those sites.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"

  @vcr site_tags_add
  Scenario: Adding a tag
    When I run "terminus site tags add --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been added to [[test_site_name]]
    """

  @vcr site_tags_list
  Scenario: Failing to add a tag because it has already been ascribed to the site by the given organization
    When I run "terminus site tags add --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    This site already has the tag testtag associated with the organization [[enterprise_org_uuid]].
    """
    Then I should not get:
    """
    Tag "testtag" has been added to [[test_site_name]]
    """

  @vcr site_tags_remove
  Scenario: Removing a tag
    When I run "terminus site tags remove --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been removed from [[test_site_name]]
    """

  @vcr site_tags_list
  Scenario: Listing a site's tags
    When I run "terminus site tags list --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --format=json"
    Then I should get:
    """
    {"tags":["testtag"]}
    """

  @vcr site_tags_list_empty
  Scenario: Failing to list a site's tags because it hasn't any
    When I run "terminus site tags list --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --format=json"
    Then I should get:
    """
    {"tags":[]}
    """
