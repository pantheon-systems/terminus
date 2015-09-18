Feature: Deploy

  Scenario: Deploy dev to test
    @vcr site-deploy
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site deploy --site=[[test_site_name]] --env=test --sync-content --note='Deploy test'"
    Then I should get "."
    Then I should get "."
    Then I should get:
    """
    Deploying code to "test", and cloning files from "live", and cloning database from "live"
    """
