Feature: Committing code to an environment's branch
  In order to maintain my git repository for my site
  As a user
  In need to be able to commit changes made to the site on the server

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_code_commit
  Scenario: Committing a change
    When I run "terminus site code commit --site=[[test_site_name]] --env=dev --message='Behat test commit' --yes"
    And I run "terminus site code log --site=[[test_site_name]] --env=dev"
    Then I should get: "Behat test commit"
