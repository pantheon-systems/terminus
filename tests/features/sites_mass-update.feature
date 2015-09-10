Feature: Site mass-update

  Scenario: Mass-Update Sites
    @vcr sites-mass-update
    When I run "terminus sites mass-update --yes"
    Then I should get one of the following: "Backing up, Needs update"
