Feature: Merging into an environment from dev
  In order to work collaboratively
  As a user
  I need to be able to merge from the dev environment.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr multidev-merge-from-dev.yml
  Scenario: Merge the dev environment into a multidev environment
    When I run "terminus multidev:merge-from-dev [[test_site_name]].multidev"
    Then I should get:
    """
    Merged the dev environment into multidev.
    """
