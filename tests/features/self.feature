Feature: CLI Commands
  In order to control Terminus
  As a user
  I need to be able to check and clear system files.

  Scenario: Displaying Terminus information
    When I run "terminus self:info"
    Then I should see a table with rows like:
    """
    PHP binary
    PHP version
    php.ini used
    Terminus project config
    Terminus root dir
    Terminus version
    Operating system
    """

  Scenario: Deleting the Terminus cache
    When I run "terminus self:clear-cache"
    Then I should get the notice:
    """
    The local Terminus cache has been cleared.
    """
    And I should have no cached commands

  Scenario: Dumping Terminus configuration
    When I run "terminus self:config:dump"
    Then I should get:
    """
      key: tokens_dir
    """
