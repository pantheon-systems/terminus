Feature: Committing code to an environment's branch
  In order to maintain my git repository for my site
  As a user
  In need to be able to commit changes made to the site on the server

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_code_commit
  Scenario: Committing a change
    When I run "terminus env:commit [[test_site_name]].dev --message='Behat test commit'"
    And I run "terminus env:code-log [[test_site_name]].dev"
    Then I should get: "Behat test commit"
