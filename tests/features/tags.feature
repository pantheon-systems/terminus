Feature: Tagging organizational sites
  In order to organize and categorize sites
  As a user
  I need to be able to apply tags to those sites.

  @vcr site-tags-add
  Scenario: Adding a tag
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"
    When I run "terminus site tags add --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been added to [[test_site_name]]
    """

  @vcr site-tags-remove
  Scenario: Removing a tag
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"
    When I run "terminus site tags remove --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been removed from [[test_site_name]]
    """
