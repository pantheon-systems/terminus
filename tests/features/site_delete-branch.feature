Feature: Deleting a site's branches
  In order to work collaboratively
  As a user
  I need to be able to delete a site's branches.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_delete-branch
  Scenario: Deleting a branch
    When I run "terminus site delete-branch --site=[[test_site_name]] --branch=multidev --yes"
    Then I should get:
    """
    Deleted Multidev environment branch "multidev"
    """

  @vcr site_delete-branch_none
  Scenario: Failing to delete branches when the site hasn't any
    When I run "terminus site delete-branch --site=[[test_site_name]] --yes"
    Then I should get:
    """
    The site [[test_site_name]] has no branches which may be deleted.
    """
