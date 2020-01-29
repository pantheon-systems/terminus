Feature: Install plugins
  In order to manage custom Terminus plugins
  As a user
  I need to be able to install custom terminus plugins

  Scenario: Show error when invoked without a plugin project
    Given I am using "managed" plugins
    When I run "terminus plugin:install"
    Then I should see an error message: Usage: terminus plugin:<install|add>

  Scenario: Show error when invoked with an invalid plugin project
    Given I am using "managed" plugins
    When I run "terminus plugin:install not-a-valid-plugin-zzz"
    Then I should see an error message: not-a-valid-plugin-zzz is not a valid Packagist project

  Scenario: Show notice when invoked with an existing plugin project
    Given I am using "managed" plugins
    When I run "terminus plugin:install pantheon-systems/terminus-secrets-plugin"
    Then I should see a notice message: terminus-secrets-plugin is already installed

  Scenario: Install a plugin from packagist
    Given I am using "managed-ignored" plugins
    And I empty the "managed-ignored" plugins
    When I run "terminus plugin:install pantheon-systems/terminus-secrets-plugin"
    And I run "terminus plugin:list"
    Then I should see a table with rows like: "terminus-secrets-plugin"
