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
