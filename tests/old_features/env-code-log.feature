Feature: Listing commits on an environment
  In order to maintain my site
  As a user
  In need to be able to list the commits on a branch

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-code-log.yml
  Scenario: Listing the commit log of a site
    When I run "terminus env:code-log [[test_site_name]].dev"
    Then I should get: "Initial Commit"
