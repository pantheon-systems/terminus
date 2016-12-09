Feature: Locking and unlocking a site
  In order to quickly take down my site in an emergency
  As a user
  I need to be able to lock and unlock the site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr lock-add-remove.yml
  Scenario: Locking and unlocking an environment
    When I run "terminus lock:enable [[test_site_name]].dev pantheon password"
    Then I should get: "[[test_site_name]].dev has been locked."

    When I run "terminus lock:disable [[test_site_name]].dev"
    Then I should get: "[[test_site_name]].dev has been unlocked."

  @vcr lock-info.yml
  Scenario: Get lock info for an environment
    When I run "terminus lock:info [[test_site_name]].dev"
    Then I should get: "---------- ----------"
    And I should get: "Locked?    true"
    And I should get: "Username   pantheon"
    And I should get: "Password   password"
    And I should get: "---------- ----------"
