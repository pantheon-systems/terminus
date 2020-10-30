Feature: Deleting a site
  In order to keep my sites list maintained
  As a user
  I need to be able to delete sites.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated

  Scenario: Deleting a site
    Given a site named "[[test_site_name]]" already exists
    When I run "terminus site:delete [[test_site_name]] --yes"
    Then I should get the notice: "Deleted [[test_site_name]] from Pantheon"
    And a site named "[[test_site_name]]" does not exist

  Scenario: Failing to delete a site that does not exist
    Given a site named "[[test_site_name]]" does not already exist
    When I run "terminus site:delete [[test_site_name]] --yes"
    Then I should not get: "Deleted [[test_site_name]] from Pantheon"
    And I should get the error: "Could not locate a site your user may access identified by [[test_site_name]]."
