Feature: SSH Keys
  In order to work on my Pantheon site
  As a user
  I need to be able to manage my SSH keys.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated

  @vcr ssh-keys_list
  Scenario: List SSH keys
    When I run "terminus ssh-key:list"
    Then I should get one of the following: "Fingerprint, You do not have any SSH keys saved."

  @vcr ssh-keys_add
  Scenario: Add an SSH key
    When I run "terminus ssh-key:add tests/config/dummy_key.pub"
    Then I should get: "Added SSH key from file tests/config/dummy_key.pub"

  @vcr ssh-keys_delete
  Scenario: Delete an SSH key
    When I run "terminus ssh-key:remove 11111111111111111111111111111"
    Then I should get: "Deleted SSH key 11111111111111111111111111111!"

