Feature: Authorization command
  In order to use Terminus
  As a user
  I need to be able to log in to the system.

  @vcr auth-login.yml
  Scenario: Logging in
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]]"
    Then I should get: "Logging in via machine token."

  @vcr auth-login.yml
  Scenario: Logging in while in debug mode does not expose sensitive information
    Given I am not authenticated
    When I run "terminus auth:login --machine-token=[[machine_token]] -vvv"
    Then I should get: "Logging in via machine token."
    And I should get:
    """
Body: {"machine_token":"**HIDDEN**","client":"terminus"}
[debug] #### RESPONSE ####
Headers: {"Date":["Tue, 16 Aug 2016 22:51:26 GMT"],"Content-Type":["application\/json; charset=utf-8"],"Content-Length":["182"],"Connection":["keep-alive"],"X-Pantheon-Trace-Id":["f535ebd0-6403-11e6-9844-1524ed28772c"],"X-Frame-Options":["deny"],"Access-Control-Allow-Methods":["GET"],"Access-Control-Allow-Headers":["Origin, Content-Type, Accept"],"Cache-Control":["no-cache"],"Pragma":["no-cache"],"Vary":["Accept-Encoding"],"Strict-Transport-Security":["max-age=31536000"]}
Data: {"session":"**HIDDEN**","expires_at":1473807086,"user_id":"11111111-1111-1111-1111-111111111111"}
  """

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
    Your saved machine tokens have been deleted and you have been logged out.
    """
    And I run "terminus auth:whoami"
    Then I should get: "You are not logged in."
    And I should not get:
    """
    [[username]]
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
