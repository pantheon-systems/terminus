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
    Then I should get one of the following: "Fingerprint, You do not have any SSH keys saved."

  @vcr ssh-keys_add
  Scenario: Add an SSH key
    When I run "terminus ssh-keys add --file=tests/config/dummy_key.pub"
    Then I should get: "Added SSH key from file tests/config/dummy_key.pub"

  @vcr ssh-keys_delete
  Scenario: Delete an SSH key
    When I run "terminus ssh-keys delete --fingerprint=a3c83331b42a397f970913505ab4cd4f"
    Then I should get: "Deleted SSH key a3c83331b42a397f970913505ab4cd4f."

  @vcr ssh-keys_delete_all
  Scenario: Delete all SSH keys
    When I run "terminus ssh-keys delete --all --yes"
    And I run "terminus ssh-keys list"
    Then I should get: "You do not have any SSH keys saved."
