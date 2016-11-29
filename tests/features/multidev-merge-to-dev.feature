Feature: Merging into dev from an environment
  In order to work collaboratively
  As a user
  I need to be able to merge into the dev environment.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr multidev-merge-to-dev.yml
  Scenario: Merge a multidev to dev environment
    When I run "terminus multidev:merge-to-dev [[test_site_name]].multidev"
    Then I should get:
    """
    Merged the multidev environment into dev
    """
