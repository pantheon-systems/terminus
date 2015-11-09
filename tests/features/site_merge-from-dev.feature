Feature: Merging into an environment from dev
  In order to work collaboratively
  As a user
  I need to be able to merge from the dev environment.

  @vcr site_merge-from-dev
  Scenario: Merge the Dev Environment into a Multidev Environment
    Given I am authenticated
    When I run "terminus site merge-from-dev --site=[[test_site_name]] --env=stuff"
    Then I should get: "."
    Then I should get: "."
    Then I should get:
    """
    Merge code from master into "stuff"
    """
