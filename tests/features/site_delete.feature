Feature: Deleting a site
  In order to keep my sites list maintained
  As a user
  I need to be able to delete sites.

  @vcr site-delete
  Scenario: Delete Site
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site delete --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Deleted [[test_site_name]]!
    """
