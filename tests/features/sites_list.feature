Feature: Listing sites
  In order to administer my sites
  As a user
  I need to be able to list those sites.

  @vcr sites_list_empty
  Scenario: JSON List Sites
    Given I am authenticated
    When I run "terminus sites list --format=json"
    Then I should get:
    """
    []
    """

  @vcr sites_list_empty
  Scenario: List Sites
    Given I am authenticated
    When I run "terminus sites list"
    Then I should get:
    """
    You have no sites
    """

  @vcr sites_list
  Scenario: List Sites
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list"
    Then I should get:
    """
    [[test_site_name]]
    """

  @vcr sites_list
  Scenario: List Team Sites
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list --team"
    Then I should not get:
    """
    enterprise-site-yo
    """

  @vcr sites_list
  Scenario: List Organization Sites
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list --org=34b1ba6e-d59e-489b-9179-9121722a1bc1"
    Then I should not get:
    """
    [[test_site_name]]
    """
