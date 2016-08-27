Feature: Tagging organizational sites
  In order to organize and categorize sites
  As a user
  I need to be able to apply tags to those sites.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[organization_name]]"

  @vcr site_tags_add
  Scenario: Adding a tag
    When I run "terminus tag:add testtag --site=[[test_site_name]] --org=[[organization_name]]"
    Then I should get:
    """
    Tag "testtag" has been added to [[test_site_name]]
    """

  @vcr site_tags_list
  Scenario: Listing a site's tags
    When I run "terminus tag:list --site=[[test_site_name]] --org=[[organization_name]] --format=json"
    Then I should get:
    """
    {"tags":["testtag"]}
    """

  @vcr site_tags_remove
  Scenario: Removing a tag
    When I run "terminus site tag:remove testtag --site=[[test_site_name]] --org=[[organization_name]]"
    Then I should get:
    """
    Tag "testtag" has been removed from [[test_site_name]]
    """

  @vcr site_tags_list_empty
  Scenario: Failing to list a site's tags because it hasn't any
    When I run "terminus tags:list --site=[[test_site_name]] --org=[[organization_name]] --format=json"
    Then I should get:
    """
    {"tags":[]}
    """
