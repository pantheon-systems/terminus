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
    When I run "terminus self:clear-cache"
    Then I should get:
    """
    The local Terminus cache has been cleared.
    """

  Scenario: Dumping Terminus configuration
    When I run "terminus self:config:dump"
    Then I should get:
    """
      key: tokens_dir
    """
