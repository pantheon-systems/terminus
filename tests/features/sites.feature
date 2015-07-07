Feature: sites
  In order to develop
  As a UNIX user
  I need to be able to see a list of pantheon sites

  Scenario: Not Authed
    @vcr not-authed
  When I run "terminus auth login fake@email.com --password=BAD_PASSWORD"
    Then I should not get:
    """
    Saving session data
    """

  Scenario: Auth Login
    @vcr auth-login
    When I am authenticating
    Then I should get:
    """
    Saving session data
    """

  Scenario: List Sites
    @vcr list-sites
    Given I am authenticating
    When I run "terminus sites list"
    Then I should get:
    """
    Name
    """
