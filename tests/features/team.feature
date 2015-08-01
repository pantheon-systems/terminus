Feature: Site Team

  Scenario: Adding a team member
    @vcr site-team-add-member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site team add-member --site=[[test_site_name]] --member=[[other_user]]"
    And I list the team members on "[[test_site_name]]"
    Then I should get:
    """
    [[other_user]]
    """

  Scenario: Removing a team member
    @vcr site-team-remove-member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site team remove-member --site=[[test_site_name]] --member=[[other_user]]"
    And I list the team members on "[[test_site_name]]"
    Then I should not get:
    """
    [[other_user]]
    """
