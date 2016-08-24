Feature: Merging into an environment from dev
  In order to work collaboratively
  As a user
  I need to be able to merge from the dev environment.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_merge-from-dev
  Scenario: Merge the dev environment into a multidev environment
    When I run "terminus site merge-from-dev --site=[[test_site_name]] --env=multidev"
    Then I should get: "."
    Then I should get: "."
    Then I should get:
    """
    Merge code from master into "multidev"
    """
