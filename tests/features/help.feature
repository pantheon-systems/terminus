Feature: Help Command
  In order to understand how to use Terminus
  As a new user
  I need to be able to check documentation on system commnads.

  @vcr help_cli
  Scenario: CLI Help
    When I run "terminus help cli --format=json"
    Then I should get:
    """
    "shortdesc":"Get information about Terminus itself."
    """

  @vcr help_recursive
  Scenario: Viewing all command info recursively
    When I run "terminus help --recursive --format=json"
    Then I should get:
    """
    terminus site backups
    """
