Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth_login
  Scenario: Logging in
    When I run "terminus auth login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token"
    And I should get: "Logged in as [[username]]."

  @vcr auth_login_machine-token_invalid
  Scenario: Failing to log in via invalid machine token
    When I run "terminus auth login --machine-token=invalid"
    Then I should get: "The provided machine token is not valid."

  @vcr auth_logout
  Scenario: Logging out
    Given I am authenticated
    When I run "terminus auth logout"
    Then I should get:
    """
    Logging out of Pantheon
    """

  @vcr auth_login_via_username_and_password
  Scenario: Login
    When I run "terminus auth login [[username]] --password=[[password]]"
    Then I should get:
    """
    Logged in as [[username]]
    """

  @vcr auth_login_bad_username_and_password
  Scenario: Not Authorizing
    When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Logged in as
    """

  @vcr auth_whoami
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth whoami"
    Then I should get:
    """
    [[user_uuid]]
    """

  Scenario: Checking my user should not get any useful result when I am logged out.
    When I am not authenticated
    And I run "terminus auth whoami"
    Then I should not get:
    """
    You are authenticated as: [[user_uuid]]
    """
