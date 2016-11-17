Feature: Site Deployment
  In order to publish a site to the internet
  As a user
  I need to be able to deploy sites on Pantheon.

  Background: I am authenticated and have a site named [[test_site_name]] on which I deploy
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_deploy
  Scenario: Deploy dev to test
    When I run "terminus env:deploy [[test_site_name]].test --note='Deploy test' --sync-content"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Deploying code to "test", and cloning files from "live", and cloning database from "live"
    """

  @vcr site_deploy_no_changes
  Scenario: Failing to deploy dev to test because there are no changes to deploy
    When I run "terminus env:deploy [[test_site_name]].test --note='Deploy test' --sync-content"
    Then I should get: "There is nothing to deploy."

  @vcr site_init-env
  Scenario: Initializing test when it has not been previously initialized
    When I run "terminus env:deploy [[test_site_name]].test --note='First deploy to live' --sync-content"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Deploying code to "test", and cloning files from "live", and cloning database from "live"
    """
