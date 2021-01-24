Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  Scenario: Logging in
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]]"
    Then I should get the notice: "Logging in via machine token."
    And I am authenticated as "[[user_id]]"

  Scenario: Logging in while in debug mode does not expose sensitive information
    When I run "terminus auth:login --machine-token=[[machine_token]] -vvv"
    Then I should get the notice: "Logging in via machine token."
    And I should not get: "[[machine_token]]"

  Scenario: Failing to log in via invalid machine token
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=invalid"
    Then I should get the notice: "Logging in via machine token."
    And I should get the error: "Client error: `POST [[host_protocol]]://[[host]]/api/authorize/machine-token` resulted in a `401 Unauthorized` response:"
    And I should not be authenticated

  Scenario: Failing to log in automatically when no machine tokens have been saved
    Given I have no saved machine tokens
    When I run "terminus auth:login"
    Then I should get the error: "Please visit the dashboard to generate a machine token:"

  Scenario: Logging out
    Given I am authenticated
    When I run "terminus auth:logout"
    Then I should get the notice: "Your saved machine tokens have been deleted and you have been logged out."
    And I should have no saved machine tokens
    And I should not be authenticated

  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth:whoami"
    Then I should get: "[[username]]"

  Scenario: Check which user I am by ID
    Given I am authenticated
    When I run "terminus auth:whoami --fields=id"
    Then I should get: "[[user_id]]"
    And I should not get: "[[username]]"

  Scenario: Checking my user should not get any useful result when I am logged out.
    When I am not authenticated
    And I run "terminus auth:whoami"
    Then I should get the notice: "You are not logged in."
    And I should not get: "[[username]]"
