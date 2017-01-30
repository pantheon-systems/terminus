Feature: Listing a site's environments
  In order to administer my site
  As a user
  I need to be able to list all of its environments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-list.yml
  Scenario: Failing to list multidevs when there aren't any
    When I run "terminus multidev:list [[test_site_name]] --format=json"
    Then I should get: "You have no multidev environments"
    And I should get: "[]"

  @vcr site-info.yml
  Scenario: Listing all multidev environments belonging to a site
    When I run "terminus multidev:list [[test_site_name]]"
    Then I should get: "---------- --------------------- -------------------------------------- --------------- --------- --------------"
    And I should get: "Name       Created               Domain                                 OnServer Dev?   Locked?   Initialized?"
    And I should get: "---------- --------------------- -------------------------------------- --------------- --------- --------------"
    And I should get: "multidev   2016-08-16 22:09:01   multidev-[[test_site_name]].pantheonsite.io   true            false     true"
    And I should get: "---------- --------------------- -------------------------------------- --------------- --------- --------------"
