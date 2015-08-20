Feature: Site Organization Tags

  Scenario: Adding a tag
    @vcr site-tags-add
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"
    When I run "terminus site tags add --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been added to [[test_site_name]]
    """

  Scenario: Removing a tag
    @vcr site-tags-remove
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"
    When I run "terminus site tags remove --site=[[test_site_name]] --org=[[enterprise_org_uuid]] --tag=testtag"
    Then I should get:
    """
    Tag "testtag" has been removed from [[test_site_name]]
    """
