Feature: Deploy

  Scenario: Deploy dev to test
    @vcr site-deploy
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site deploy --site=[[test_site_name]] --from=dev --env=test --note='Deploy test'"
    Then I should get "Woot! Code deployed to test"
