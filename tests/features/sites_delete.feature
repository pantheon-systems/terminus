Feature: sites delete

  Scenario: Delete Site
    @vcr sites-delete
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites delete --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Deleted [[test_site_name]]!
    """
