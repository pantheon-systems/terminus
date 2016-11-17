Feature: Locking and unlocking a site
  In order to quickly take down my site in an emergency
  As a user
  I need to be able to lock and unlock the site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_lock
  Scenario: Locking and unlocking an environment
    When I run "terminus lock:add [[test_site_name]].dev pantheon password"
    Then I should get: "[[test_site_name]].dev has been locked."

    When I run "terminus lock:remove [[test_site_name]].dev"
    Then I should get: "[[test_site_name]].dev has been unlocked."

  @vcr site_lock_info
  Scenario: Get lock info for an environment
    When I run "terminus lock:info [[test_site_name]].dev"
    Then I should get: "---------- ----------"
    And I should get: "Locked?    true"
    And I should get: "Username   pantheon"
    And I should get: "Password   password"
    And I should get: "---------- ----------"
