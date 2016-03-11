Feature: SSH Keys
  In order to work on my Pantheon site
  As a user
  I need to be able to manage my SSH keys.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr ssh-keys_list
  Scenario: List SSH keys
    When I run "terminus ssh-keys list"
    Then I should get one of the following: "Hex, You do not have any SSH keys saved."
