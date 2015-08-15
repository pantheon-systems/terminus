Feature: sites create

  Scenario: Create Site
    @vcr sites-create
    Given I am authenticated
    When I run "terminus sites create --site=[[test_site_name]] --label=[[test_site_name]] --upstream=WordPress"
    Then I should get:
    """
    Pow! You created a new site!
    """
