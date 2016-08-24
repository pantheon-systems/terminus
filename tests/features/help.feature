Feature: Help Command
  In order to understand how to use Terminus
  As a new user
  I need to be able to check documentation on system commnads.

  Scenario: Getting help on the CLI command
    When I run "terminus help cli --format=json"
    Then I should get:
    """
    "shortdesc":"Get information about Terminus itself."
    """

  Scenario: Viewing all commands' info recursively
    When I run "terminus help --recursive --format=json"
    Then I should get:
    """
    terminus site backups
    """
