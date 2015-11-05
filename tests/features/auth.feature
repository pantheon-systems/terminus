Feature: auth

  Scenario: Not Authorizing
    @vcr auth-login-bad
    When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Saving session data
    """

  Scenario: Login
    @vcr auth-login
    When I run "terminus auth login [[username]] --password=[[password]]"
    Then I should get:
    """
    Logged in as [[user_uuid]]
    """

  Scenario: Check Which User I Am
    @vcr auth-whoami
    Given I am authenticated
    When I run "terminus auth whoami"
    Then I should get:
    """
    You are authenticated as: [[user_uuid]]
    """

  Scenario: Logout
    @vcr auth-logout
    Given I am authenticated
    When I run "terminus auth logout"
    Then I should get:
    """
    Logging out of Pantheon
    """

  Scenario: Checking My User When Logged Out
    @vcr auth-whoami-logged-out
    When I run "terminus auth logout"
    And I run "terminus auth whoami"
    Then I should not get:
    """
    You are authenticated as: [[user_uuid]]
    """
