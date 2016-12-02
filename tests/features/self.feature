Feature: CLI Commands
  In order to control Terminus
  As a user
  I need to be able to check and clear system files.

  Scenario: Displaying Terminus information
    When I run "terminus self:info"
    Then I should get:
    """
    Terminus version
    """

  @vcr self-env-cache-clear.yml
  Scenario: Deleting the Terminus cache
    Given I am authenticated
    And I have at least "1" saved machine tokens
    When I run "terminus auth:whoami"
    Then I should get:
    """
    [[username]]
    """
    And I run "terminus self:clear-cache"
    Then I should get:
    """
    Your saved machine tokens have been deleted and you have been logged out.
    """
    And I run "terminus auth:whoami"
    Then I should get: "You are not logged in."
    And I run "terminus auth:login"
    """
    Please visit the dashboard to generate a machine token:
    """

  Scenario: Dumping Terminus configuration
    When I run "terminus self:config:dump"
    Then I should get:
    """
      key: tokens_dir
    """
