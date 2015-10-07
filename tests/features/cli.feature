Feature: cli

  #Scenario: Clearing Cache
    #@vcr cli-clear-cache
    #When I run "touch ~/.terminus/cache/testfile"
    #And I run "terminus cli clear-cache"
    #And I run "ls ~/.terminus/cache"
    #Then I should not get:
    #"""
    #testfile
    #"""

  Scenario: Dumping Commands
    @vcr cli-cmd-dump
    When I run "terminus cli cmd-dump"
    Then I should get:
    """
    Dump the list of installed commands, as JSON.
    """

  Scenario: Generate Tab Completions
    @vcr cli-completions

  Scenario: CLI Information
    @vcr cli-info
    When I run "terminus cli info"
    Then I should get:
    """
    Terminus version
    """

  Scenario: Dumping Parameters
    @vcr cli-param-dump
    When I run "terminus cli param-dump"
    Then I should get:
    """
    Answer yes to all prompts
    """

  Scenario: Clearing Session
    @vcr cli-session-clear
    When I run "touch ~/.terminus/cache/session"
    And I run "terminus cli session-clear"
    And I run "ls ~/.terminus/cache"
    Then I should not get:
    """
    session
    """

  Scenario: Dumping Empty Session
    @vcr cli-session-dump-empty
    When I run "terminus auth logout"
    And I run "terminus cli session-dump --format=json"
    Then I should get:
    """
    false
    """

  Scenario: Dumping Session
    @vcr cli-session-dump
    When I run "terminus auth login [[username]] --password=[[password]]"
    And I run "terminus cli session-dump"
    Then I should get:
    """
    [email] => devuser@pantheon.io
    """

  Scenario: Print Version
    @vcr cli-version
    When I run "terminus cli version"
    Then I should get:
    """
    Terminus version
    """
