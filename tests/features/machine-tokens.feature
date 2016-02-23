Feature: Machine Tokens command
  In order to manage my devices
  As a user
  I need to be able to view and delete my machine tokens.

  Background: I've authenticated via machine token
    Given I log in via machine token

  @vcr machine-tokens_list
  Scenario: List machine tokens
    When I run "terminus machine-tokens list"
    Then I should get:
    """
    [[machine_token_id]]
    """

  @vcr machine-tokens_list_empty
  Scenario: List machine tokens
    When I run "terminus machine-tokens list"
    Then I should get:
    """
    You have no machine tokens.
    """

  @vcr machine-tokens_delete
  Scenario: Delete machine token
    When I run "terminus machine-tokens delete --machine-token-id=[[machine_token_id]] --yes"
    Then I should get:
    """
    Deleted [[machine_token_device]]!
    """
