Feature: Search for plugins
  In order to manage custom Terminus plugins
  As a user
  I need to be able to search for custom terminus plugins on Packagist

  Scenario: Show error when no search keyword is provided
    When I run "terminus plugin:search"
    Then I should see an error message: Usage: terminus plugin:<search|find|locate> <string>

  Scenario: Show result set in table
    When I run: terminus plugin:search "pantheon-systems/terminus-secrets-plugin"
    Then I should see a table with the headers: "Name, Status, Description"
