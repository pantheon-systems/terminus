Feature: ASCII Art display

  Scenario: Display fist art
    When I run "terminus art fist"
    Then I should get: ".+."

  Scenario: Display Druplicon art
    When I run "terminus art druplicon"
    Then I should get: ".."

  Scenario: Display WordPress art
    When I run "terminus art wordpress"
    Then I should get: "............."

  Scenario: Display unicorn art
    When I run "terminus art unicorn"
    Then I should get: "\"

  Scenario: Display available art
    When I run "terminus art"
    Then I should get: ""
    And I should get: "Available Art"
    And I should get: "============="
    And I should get: ""
    And I should get: "* druplicon"
    And I should get: "* fist"
    And I should get: "* hello"
    And I should get: "* rocket"
    And I should get: "* unicorn"
    And I should get: "* wordpress"

  Scenario: Display random art
    When I run "terminus art random"
    Then I should get one of the following: ">\/7, +?????????=., .xWWXo;;;;;,'., ......''......, (( )), Hello World!"



  Scenario: Fail to display non-existent artwork
    When I run "terminus art invalid"
    Then I should get: ""
    And I should get: "There is no source for the requested invalid artwork"
