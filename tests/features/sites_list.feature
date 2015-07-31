Feature: sites

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
