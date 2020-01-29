Feature: Update plugins
  In order to manage custom Terminus plugins
  As a user
  I need to be able to update custom terminus plugins

  Scenario: Show notice when no plugins are installed
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    When I run "terminus self:plugin:update"
    Then I should see a notice message: You have no plugins installed

  Scenario: Show error when invoked with a plugin that hasn't been installed yet
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    When I run "terminus self:plugin:update plugin-does-not-exist"
    Then I should see an error message: plugin-does-not-exist is not installed

  Scenario: Notify user when plugin is already up-to-date
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    And I run "terminus self:plugin:install pantheon-systems/terminus-secrets-plugin"
    When I run "terminus self:plugin:update terminus-secrets-plugin"
    Then I should see a notice message: Updating terminus-secrets-plugin
    And I should see a notice message: Already up-to-date

  Scenario: Update a plugin
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    And I run "terminus self:plugin:install pantheon-systems/terminus-secrets-plugin"
    And I downgrade the "terminus-secrets-plugin" plugin to "1.1.0"
    When I run "terminus self:plugin:list"
    Then I should see a table with rows like: "1.1.0"
    When I run "terminus self:plugin:update terminus-secrets-plugin"
    And I run "terminus self:plugin:list"
    Then I should not get one of the following: "1.1.0"
