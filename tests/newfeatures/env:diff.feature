Feature: Diffing environments
  In order to maintain my git repository for my site
  As a user
  In need to be able to check for changes in the code on branches

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_code_diffstat
  Scenario: Looking for changes on the server
    When I run "terminus env:diff dev --site=[[test_site_name]]"
    Then I should get: "Deletions"
    And I should not get: "No changes on the server."

  @vcr site_code_diffstat_empty
  Scenario: Looking for changes on the server when there are none
    When I run "terminus env:diff dev --site=[[test_site_name]]"
    Then I should get: "No changes on server."
