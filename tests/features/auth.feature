Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth-login.yml
  Scenario: Logging in
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]]"
    Then I should get the notice "Logging in via machine token."
    And I should be logged in

  @vcr auth-login.yml
  Scenario: Logging in while in debug mode does not expose sensitive information
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]] -vvv"
    Then I should get the notice "Logging in via machine token."
    And I should not get "[[machine_token]]"

  @vcr auth-login-machine-token-invalid.yml
  Scenario: Failing to log in via invalid machine token
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=invalid"
    Then I should get the notice "Logging in via machine token."
    And I should get the error "Client error: `POST https://[[host]]/api/authorize/machine-token` resulted in a `401 Unauthorized` response:"
    And I should get "Authorization failed. Please check that your machine token is valid."
    And I should not be logged in

  Scenario: Failing to log in by saved token when no such user's was saved
    Given I have no saved machine tokens
    When I run "terminus auth:login --email=invalid"
    Then I should get the error "Could not find a saved token identified by invalid."

  @vcr auth-login.yml
  Scenario: Logging in automatically when a single machine tokens has been saved
    Given I have exactly "1" saved machine token
    When I run "terminus auth:login"
    Then I should get the notice "Found a machine token for [[username]]."
    And I should get the notice "Logging in via machine token."

  @vcr auth-login.yml
  Scenario: Failing to log in automatically when multiple machine tokens have been saved
    Given I have at least "2" saved machine tokens
    When I run "terminus auth:login"
    Then I should get the error "Tokens were saved for the following email addresses:"
    And I should get "You may log in via `terminus auth:login --email=<email>`, or you may visit the dashboard to generate a machine token:"

  Scenario: Failing to log in automatically when no machine tokens have been saved
    Given I have no saved machine tokens
    When I run "terminus auth:login"
    Then I should get the error "Please visit the dashboard to generate a machine token:"

  @vcr auth-logout.yml
  Scenario: Logging out
    Given I am authenticated
    When I run "terminus auth:logout"
    Then I should get the notice "Your saved machine tokens have been deleted and you have been logged out."
    And I should be logged out

  @vcr auth-whoami.yml
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth:whoami"
    Then I should get "[[username]]"

  @vcr auth-whoami.yml
  Scenario: Displaying fields in a session in a table
    Given I am authenticated
    When I run "terminus auth:whoami --format=table"
    Then I should see a table with rows like:
    """
    First Name
    Last Name
    Email
    ID
    """
    And I should get "[[username]]"

  Scenario: Checking my user should not get any useful result when I am logged out.
    When I am not authenticated
    And I run "terminus auth:whoami"
    Then I should get the notice "You are not logged in."
    And I should not get "[[username]]"
