Feature: Site organizations

  Scenario: Adding a supporting organization to a site
    @vcr site_organizations_add
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations add --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Added "[[enterprise_org_name]]" as a supporting organization
    """

  Scenario: Failing to add an invalid organization
    @vcr site_organizations_add_invalid
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations add --site=[[test_site_name]] --org=badorgname"
    Then I should get:
    """
    Organization is either invalid or you are not a member.
    """

  Scenario: Failing to add an organizaiton which is already a member
    @vcr site_organizations_add_duplicate
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations add --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Membership already exists. Try updating it instead.
    """

  Scenario: Removing a supporting organization from a site
    @vcr site_organizations_remove
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    Removed supporting organization
    """

  Scenario: Failing to remove an invalid organization
    @vcr site_organizations_remove_invalid
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=badorgname"
    Then I should get:
    """
    Organization is either invalid or you are not a member.
    """

  Scenario: Failing to remove an organization which is not a member
    @vcr site_organizations_remove_invalid
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site organizations remove --site=[[test_site_name]] --org=[[enterprise_org_name]]"
    Then I should get:
    """
    [[enterprise_org_uuid]] is not a member of [[test_site_name]]
    """
