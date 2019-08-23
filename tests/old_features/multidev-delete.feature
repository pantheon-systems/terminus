Feature: Deleting a site's multidev environments
  In order to work collaboratively
  As a user
  I need to be able to remove multidev environments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr multidev-delete.yml
  Scenario: Deleting a multidev environment
    When I run "terminus multidev:delete [[test_site_name]].multidev --yes"
    Then I should get:
    """
    Deleted the multidev environment multidev.
    """

  @vcr multidev-delete.yml
  Scenario: Failing to delete a multidev environment when the specified environment does not exist
    When I run "terminus multidev:delete [[test_site_name]].invalid --yes"
    Then I should get:
    """
    Could not find an environment identified by invalid.
    """

