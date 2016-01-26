Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth_login_bad
  Scenario: Not Authorizing
    When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Saving session data
    """

  @vcr auth_login
  Scenario: Login
    When I run "terminus auth login [[username]] --password=[[password]]"
    Then I should get:
    """
    Logged in as [[user_uuid]]
    """

  @vcr auth_whoami
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth whoami"
    Then I should get:
    """
    [[user_uuid]]
    """

  @vcr auth_logout
  Scenario: Logout
    Given I am authenticated
    When I run "terminus auth logout"
    Then I should get:
    """
    Logging out of Pantheon
    """

  @vcr auth_whoami_logged-out
  Scenario: Checking My User When Logged Out
    When I run "terminus auth logout"
    And I run "terminus auth whoami"
    Then I should not get:
    """
    You are authenticated as: [[user_uuid]]
    """

  @vcr auth_logout
  Scenario: Trying to use an auth-restricted command while logged out
    Given I am not authenticated
    When I run "terminus sites list"
    Then I should get:
    """
    You are not logged in. Run `auth login` to authenticate or `help auth login` for more info.
    """

  @vcr auth_login_machine-token
  Scenario: Logging in via machine token
    When I run "terminus auth login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token"
    And I should get: "Saving session data"
    And I should get: "Logged in as [[username]]."

  #Scenario: Failing to log in via invalid machine token
    #@vcr auth_login_machine_token_invalid
    #When I run "terminus auth login --machine-token=invalid"
    #Then I should get:
    #"""
    #Authorization failed
    #"""

  #Scenario: Logging in successfully after session has expired
    #@vcr auth_login_machine_token_expired
    #When I log in via machine token
    #And I expire my session
    #And I list the sites
    #Then I should get:
    #"""
    #[[test_site_name]]
    #"""
