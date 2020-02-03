Feature: Uninstall plugins
  In order to manage custom Terminus plugins
  As a user
  I need to be able to uninstall custom terminus plugins

  Scenario: Show error when invoked without a plugin project
    Given I am using "managed-ignored" plugins
    When I run "terminus self:plugin:uninstall"
    Then I should see an error message: Usage: terminus self:plugin:<uninstall|remove|delete>

  Scenario: Show error when invoked with a plugin that hasn't been installed yet
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    When I run "terminus self:plugin:uninstall plugin-does-not-exist"
    Then I should see an error message: plugin-does-not-exist is not installed

  Scenario: Uninstall a plugin
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    When I run "terminus self:plugin:install pantheon-systems/terminus-secrets-plugin"
    And I run "terminus self:plugin:list"
    Then I should see a table with rows like: "terminus-secrets-plugin"
    When I run "terminus self:plugin:uninstall terminus-secrets-plugin"
    Then I should see a notice message: terminus-secrets-plugin was removed successfully
    When I run "terminus self:plugin:list"
    Then I should see a notice message: You have no plugins installed.
