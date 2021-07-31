Feature: ASCII Art display

  Scenario: Display fist art
    When I run "[[executable]] art fist"
    Then I should get: ".+."

  Scenario: Display Druplicon art
    When I run "[[executable]] art druplicon"
    Then I should get: ".."

  Scenario: Display WordPress art
    When I run "[[executable]] art wordpress"
    Then I should get: "............."

  Scenario: Display unicorn art
    When I run "[[executable]] art unicorn"
    Then I should get: "\"

  Scenario: Display random art
    When I run "[[executable]] art random"
    Then I should get one of the following: ">\/7, +?????????=., .xWWXo;;;;;,'., ......''......, (( )), Hello World!"

  Scenario: Fail to display non-existent artwork
    When I run "[[executable]] art invalid"
    Then I should get: ""
    And I should get: "There is no source for the requested invalid artwork"
