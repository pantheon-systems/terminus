Feature: Organization sites

  Scenario: List an organization's sites
    @vcr organizations_sites_list
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites list --org=[[enterprise_org_name]]"
    Then I should get:
    """
    [[test_site_name]]
    """

  Scenario: Add a site to an organization
    @vcr organizations_sites_add
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Added a site to the organization
    """

  Scenario: Fail to add an invalid site to an organization
    @vcr organizations_sites_add_invalid
    Given I am authenticated
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=invalid --yes"
    Then I should get:
    """
    Cannot find site with the name "invalid"
    """

  Scenario: Fail to add a duplicate member site to an organization
    @vcr organizations_sites_add_duplicate
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites add --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    [[test_site_name]] is already a member of [[enterprise_org_name]]
    """

  Scenario: Remove a site from an organization
    @vcr organizations_sites_remove
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_name]]"
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Removed a site to the organization
    """

  Scenario: Fail to remove an invalid site from an organization
    @vcr organizations_sites_remove_invalid
    Given I am authenticated
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=invalid --yes"
    Then I should get:
    """
    invalid is not a member of [[enterprise_org_name]]
    """

  Scenario: Fail to remove a non-member site from an organization
    @vcr organizations_sites_remove_duplicate
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus organizations sites remove --org=[[enterprise_org_name]] --site=[[test_site_name]] --yes"
    Then I should get:
    """
    [[test_site_name]] is not a member of [[enterprise_org_name]]
    """

