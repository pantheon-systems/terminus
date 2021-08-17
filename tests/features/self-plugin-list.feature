Feature: List installed plugins
  In order to manage custom terminus plugins
  As a user
  I need to be able to list installed terminus plugins

  Scenario: Show message when no plugins are installed
    Given I am using "default" plugins
    When I run "terminus self:plugin:list"
    Then I should see a warning message: You have no plugins installed.

  Scenario: Show all installed plugins
    Given I am using "managed" plugins
    And I run "terminus self:plugin:reload"
    When I run "terminus self:plugin:list"
    Then I should see a table with the headers: "Name, Description, Installed Version"
    And I should see a table with rows like: "terminus-build-tools-plugin"
