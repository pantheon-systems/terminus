Feature: Gathering sites' aliases
  As a Pantheon user
  I need to be able to generate a list of aliases
  So that I may make use of Drush effectively.

  Background: I am authenticated
    Given I am authenticated

  @vcr sites_aliases
  Scenario: Generating aliases without printout
    When I run "terminus sites aliases"
    Then I should get one of the following: "Pantheon aliases updated, Pantheon aliases created"
    And I should not get: "pantheon.io"

  @vcr sites_aliases
  Scenario: Generating aliases with printout
    When I run "terminus sites aliases --print"
    Then I should get one of the following: "Pantheon aliases updated, Pantheon aliases created"
    And I should get: "pantheon.io"
