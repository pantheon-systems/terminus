Feature: List installed plugins
  In order to manage custom terminus plugins
  As a user
  I need to be able to list installed terminus plugins

  Scenario: Show message when no plugins are installed
    Given I am using "default" plugins
    When I run "terminus self:plugin:list"
    Then I should see a notice message: You have no plugins installed.

  Scenario: Show all installed plugins
    Given I am using "managed" plugins
    When I run "terminus self:plugin:list"
    Then I should see a table with the headers: "Name, Description, Version"
    And I should see a table with rows like: "Secrets - A Terminus plugin that allows for manipulation of a 'secrets' file for use with Quicksilver."
