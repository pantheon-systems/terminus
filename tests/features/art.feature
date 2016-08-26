Feature: ASCII Art display

  Scenario: Display fist art
    When I run "terminus art fist"
    Then I should get: "+?????????=."

  Scenario: Display Druplicon art
    When I run "terminus art druplicon"
    Then I should get: ".xWWXo;;;;;,'."

  Scenario: Display WordPress art
    When I run "terminus art wordpress"
    Then I should get: "......''......"

  Scenario: Display unicorn art
    When I run "terminus art unicorn"
    Then I should get: ">\/7"

  Scenario: Display rocket art
    When I run "terminus art rocket"
    Then I should get: "(( ))"

  Scenario: Display random art
    When I run "terminus art"
    Then I should get one of the following: ">\/7, +?????????=., .xWWXo;;;;;,'., ......''......, (( ))"

  Scenario: Fail to display non-existent artwork
    When I run "terminus art invalid"
    Then I should get:
    """
    There is no source for the requested "invalid" artwork.
    """
