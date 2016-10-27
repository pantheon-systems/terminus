Feature: Deleting a site's branches
  In order to work collaboratively
  As a user
  I need to be able to delete a site's branches.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr branch_delete.yml
  Scenario: Deleting a branch
    When I run "terminus branch:delete [[test_site_name]] new_branch"
    Then I should get:
    """
    Deleting the new_branch branch of the site [[test_site_name]].
    """
    And I should get:
    """
    Deleted Multidev environment branch "new_branch"
    """

  @vcr branch_delete_none.yml
  Scenario: Failing to delete branches when the site hasn't any
    When I run "terminus branch:delete [[test_site_name]] some_branch"
    Then I should get:
    """
    Could not find Terminus\Models\Branch "some_branch"
    """
