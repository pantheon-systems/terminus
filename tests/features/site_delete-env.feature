Feature: Deleting a site's multidev environments
  In order to work collaboratively
  As a user
  I need to be able to remove multidev environments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_delete-env
  Scenario: Deleting a multidev environment
    When I run "terminus site delete-env --site=[[test_site_name]] --env=multidev --yes"
    Then I should get:
    """
    Deleted Multidev environment "multidev"
    """

  @vcr site_delete-env_none
  Scenario: Failing to delete a multidev environment when none exist on the site
    When I run "terminus site delete-env --site=[[test_site_name]] --yes"
    Then I should get:
    """
    [[test_site_name]] does not have any multidev environments to delete.
    """

  @vcr site_delete-env_invalid
  Scenario: Failing to delete a multidev environment when a nonexistant one is asked for
    When I run "terminus site delete-env --site=[[test_site_name]] --env=invalid --yes"
    Then I should get:
    """
    The environment "invalid" was not found.
    """
