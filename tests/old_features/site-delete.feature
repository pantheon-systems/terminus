Feature: Deleting a site
  In order to keep my sites list maintained
  As a user
  I need to be able to delete sites.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-delete.yml
  Scenario: Delete Site
    When I run "terminus site:delete [[test_site_name]] --yes"
    Then I should get: "Deleted [[test_site_name]] from Pantheon"
