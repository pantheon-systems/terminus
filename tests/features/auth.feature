Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth_login_bad
  Scenario: Not Authorizing
    When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Logged in as
    """

  @vcr auth_login
  Scenario: Login
    When I run "terminus auth login [[username]] --password=[[password]]"
    Then I should get:
    """
    Logged in as [[username]]
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
    Then I should get one of the following: "You are not logged in., The provided machine token is not valid."

  @vcr auth_login_machine-token
  Scenario: Logging in via machine token
    When I run "terminus auth login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token"
    And I should get: "Logged in as [[username]]."

  @vcr auth_login_machine-token_invalid
  Scenario: Failing to log in via invalid machine token
    When I run "terminus auth login --machine-token=invalid"
    Then I should get: "The provided machine token is not valid."

  #Scenario: Logging in successfully after session has expired
    #@vcr auth_login_machine_token_expired
    #When I log in via machine token
    #And I expire my session
    #And I list the sites
    #Then I should get:
    #"""
    #[[test_site_name]]
    #"""
