Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth-login.yml
  Scenario: Logging in
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token."

  @vcr auth-login-machine-token-invalid.yml
  Scenario: Failing to log in via invalid machine token
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=invalid"
    Then I should get: "Logging in via machine token."
    And I should get: "Server error: `POST https://onebox/api/authorize/machine-token` resulted in a `500 Internal Server Error` response:"
    And I should get: "Authorization failed. Please check that your machine token is valid."

  Scenario: Failing to log in by saved token when no such user's was saved
    Given I have no saved machine tokens
    When I run "terminus auth:login --email=invalid"
    Then I should get:
    """
    Could not find a saved token identified by invalid.
    """

  Scenario: Failing to log in automatically when multiple machine tokens have been saved
    Given I have at least "2" saved machine tokens
    When I run "terminus auth:login"
    Then I should get:
    """
    Please visit the dashboard to generate a machine token:
    """

  Scenario: Failing to log in automatically when no machine tokens have been saved
    Given I have no saved machine tokens
    When I run "terminus auth:login"
    Then I should get:
    """
    Please visit the dashboard to generate a machine token:
    """

  @vcr auth-logout.yml
  Scenario: Logging out
    Given I am authenticated
    When I run "terminus auth:logout"
    Then I should get:
    """
    You have been logged out of Pantheon.
    """

  @vcr auth-whoami.yml
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth:whoami"
    Then I should get:
    """
    [[username]]
    """

  @vcr auth-whoami.yml
  Scenario: Check which user I am by ID
    Given I am authenticated
    When I run "terminus auth:whoami --fields=id"
    Then I should get:
    """
    [[user_id]]
    """
    And I should not get:
    """
    [[username]]
    """

  @vcr auth-whoami.yml
  Scenario: Displaying fields in a session in a table
    Given I am authenticated
    When I run "terminus auth:whoami --format=table --fields=email,id"
    Then I should get: "------- --------------------------------------"
    And I should get: "Email   [[username]]"
    And I should get: "ID      [[user_id]]"
    And I should get: "------- --------------------------------------"

  Scenario: Checking my user should not get any useful result when I am logged out.
    When I am not authenticated
    And I run "terminus auth:whoami"
    Then I should get: "You are not logged in."
    And I should not get:
    """
    [[username]]
    """
