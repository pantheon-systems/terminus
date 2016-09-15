Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth_login
  Scenario: Logging in
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token."

  @vcr auth_login_machine-token_invalid
  Scenario: Failing to log in via invalid machine token
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=invalid"
    Then I should get: "The provided machine token is not valid."

  @vcr auth_login
  Scenario: Logging back in automatically when there is only one token saved and no email given
    Given I have no saved machine tokens
    And I log in via machine token "[[machine_token]]"
    And I log out
    When I run "terminus auth:login"
    Then I should get: "Found a machine token for [[username]]"
    And I should get: "Logging in via machine token."

  @vcr auth_login
  Scenario: Logging in with a saved machine token by email
    Given I log in via machine token "[[machine_token]]"
    And I log out
    When I log in as "[[username]]"
    Then I should get: "Logging in via machine token."

  Scenario: Failing to log in by saved token when no such user's was saved
    Given I have no saved machine tokens
    When I run "terminus auth:login --email=invalid"
    Then I should get:
    """
    There are no saved tokens for invalid.
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

  @vcr auth_logout
  Scenario: Logging out
    Given I am authenticated
    When I run "terminus auth:logout"
    Then I should get:
    """
    You have been logged out of Pantheon.
    """

  @vcr auth_whoami
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth:whoami"
    Then I should get:
    """
    [[username]]
    """

  @vcr cli_session-dump
  Scenario: Dumping a session
    Given I am authenticated
    When I run "terminus auth:whoami -vvv"
    Then I should get:
    """
    [id] => [[user_uuid]]
    """

  Scenario: Checking my user should not get any useful result when I am logged out.
    When I am not authenticated
    And I run "terminus auth:whoami"
    Then I should get: "You are not logged in."
    And I should not get:
    """
    [[username]]
    """
