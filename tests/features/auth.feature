Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth-login-bad
  Scenario: Not Authorizing
    When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Saving session data
    """

  @vcr auth-login
  Scenario: Login
    When I run "terminus auth login [[username]] --password=[[password]]"
    Then I should get:
    """
    Logged in as [[user_uuid]]
    """

  @vcr auth-whoami
  Scenario: Check Which User I Am
    Given I am authenticated
    When I run "terminus auth whoami"
    Then I should get:
    """
    You are authenticated as: [[user_uuid]]
    """

  @vcr auth-logout
  Scenario: Logout
    Given I am authenticated
    When I run "terminus auth logout"
    Then I should get:
    """
    Logging out of Pantheon
    """

  @vcr auth-whoami-logged-out
  Scenario: Checking My User When Logged Out
    When I run "terminus auth logout"
    And I run "terminus auth whoami"
    Then I should not get:
    """
    You are authenticated as: [[user_uuid]]
    """

  #Scenario: Logging in via refresh token
    #@vcr auth_login_refresh
    #When I run "terminus auth login --refresh=[[refresh_token]]"
    #Then I should get:
    #"""
    #Logged in as [[user_uuid]]
    #"""

  #Scenario: Failing to log in via invalid refresh token
    #@vcr auth_login_refresh_invalid
    #When I run "terminus auth login --refresh=invalid"
    #Then I should get:
    #"""
    #Authorization failed
    #"""

  #Scenario: Logging in successfully after session has expired
    #@vcr auth_login_refresh_expired
    #When I log in via refresh token
    #And I expire my session
    #And I list the sites
    #Then I should get:
    #"""
    #[[test_site_name]]
    #"""
