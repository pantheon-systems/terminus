Feature: Command Information
  In order to know what I'm doing
  As a user
  I need to be prompted with help if I do something wrong

  Scenario: Passing zero arguments
    Given an empty directory
    When I run `terminus`
    Then the return code should be 0
    Then STDOUT should contain:
      """
      PHP TERMINUS: because TERMINUS
      """
