Feature: Gathering sites' aliases
  As a Pantheon user
  I need to be able to generate a list of aliases
  So that I may make use of Drush effectively.

  Background: I am authenticated
    Given I am authenticated

  @vcr sites_aliases
  Scenario: Generating aliases without printout
    Given I have at least "1" site
    When I run "terminus sites aliases"
    Then I should get one of the following: "Pantheon aliases updated, Pantheon aliases created"
    And I should not get: "pantheon.io"

  @vcr sites_aliases
  Scenario: Generating aliases with printout
    Given I have at least "1" site
    When I run "terminus sites aliases --print"
    Then I should get one of the following: "Pantheon aliases updated, Pantheon aliases created"
    And I should get: "pantheon.io"

  @vcr sites_aliases_none
  Scenario: Failing to generate aliases because I have no sites
    Given I have no sites
    When I run "terminus sites aliases --print"
    Then I should get: "although you have no sites"
    And I should not get: "pantheon.io"
