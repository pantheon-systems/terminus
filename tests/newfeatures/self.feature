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

