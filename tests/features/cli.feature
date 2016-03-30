Feature: CLI Commands
  In order to control Terminus
  As a user
  I need to be able to check and clear system files.

  @vcr cli-clear-cache
  Scenario: Clearing Cache
    When I run "touch ~/.terminus/cache/testfile"
    And I run "terminus cli cache-clear"
    And I run "ls ~/.terminus/cache"
    Then I should not get:
    """
    testfile
    """

  @vcr cli_cmd-dump
  Scenario: Dumping Commands
    When I run "terminus cli cmd-dump"
    Then I should get:
    """
    Dump the list of installed commands, as JSON.
    """

  @vcr cli_info
  Scenario: CLI Information
    When I run "terminus cli info"
    Then I should get:
    """
    Terminus version
    """

  @vcr cli_param-dump
  Scenario: Dumping Parameters
    When I run "terminus cli param-dump"
    Then I should get:
    """
    Answer yes to all prompts
    """

  @vcr cli_session-clear
  Scenario: Clearing Session
    When I run "touch ~/.terminus/cache/session"
    And I run "terminus cli session-clear"
    And I run "ls ~/.terminus/cache"
    Then I should not get:
    """
    session
    """

  @vcr cli_session-dump_empty
  Scenario: Dumping Empty Session
    When I run "terminus auth logout"
    And I run "terminus cli session-dump --format=json"
    Then I should get:
    """
    []
    """

  @vcr cli_session-dump
  Scenario: Dumping Session
    When I run "terminus auth login [[username]] --password=[[password]]"
    And I run "terminus cli session-dump"
    Then I should get:
    """
    [user_uuid] => [[user_uuid]]
    """

  @vcr cli_version
  Scenario: Print Version
    When I run "terminus cli version"
    Then I should get:
    """
    Terminus version
    """
