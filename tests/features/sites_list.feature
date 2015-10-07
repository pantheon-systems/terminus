Feature: sites

  Scenario: JSON List Sites
    @vcr sites-list-empty
    Given I am authenticated
    When I run "terminus sites list --format=json"
    Then I should get:
    """
    []
    """

  Scenario: List Sites
    @vcr sites-list-empty
    Given I am authenticated
    When I run "terminus sites list"
    Then I should get:
    """
    You have no sites
    """

  Scenario: List Sites
    @vcr sites-list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list"
    Then I should get:
    """
    [[test_site_name]]
    """

  Scenario: List Team Sites
    @vcr sites-list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list --team"
    Then I should not get:
    """
    enterprise-site-yo
    """

  Scenario: List Organization Sites
    @vcr sites-list
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites list --org=34b1ba6e-d59e-489b-9179-9121722a1bc1"
    Then I should not get:
    """
    [[test_site_name]]
    """
