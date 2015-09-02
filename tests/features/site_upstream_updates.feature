Feature: site upstream-updates

  Scenario: Check for upstream updates
    @vcr site_upstream-updates
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"
    When I run "terminus site upstream-updates --site=[[test_site_name]]"
    Then I should get:
    """
    Updates Available
    """

  Scenario: Apply upstream updates
    @vcr site_upstream-updates
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"
    When I run "terminus site upstream-updates --site=[[test_site_name]] --update --yes"
    Then I should get one of the following: "Updates applied, Apply upstream updates"
