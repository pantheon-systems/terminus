Feature: Merging into dev from an environment
  In order to work collaboratively
  As a user
  I need to be able to merge into the dev environment.

  @vcr site_merge-to-dev
  Scenario: Merge Multidev to dev environment
    Given I am authenticated
    When I run "terminus site merge-to-dev --site=[[test_site_name]] --env=stuff"
    Then I should get: "."
    Then I should get:
    """
    Merged the stuff environment into dev
    """
