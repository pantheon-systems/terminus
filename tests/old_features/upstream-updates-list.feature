Feature: Update a site with all its upstream's updates
  In order to easily maintain my site
  As a user
  I need to be able to update my site to reflect updates in its current upstream.

  Background: I am authenticated and I have a site named [[test_site_name]] using Git mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the connection mode of "[[test_site_name]]" is "git"

  @vcr upstream-updates.yml
  Scenario: Check for upstream updates and there aren't any
    When I run "terminus upstream:updates:list [[test_site_name]].dev"
    Then I should get: "There are no available updates for this site."
    And I should get: "----------- ----------- --------- --------"
    And I should get: "Commit ID   Timestamp   Message   Author"
    And I should get: "----------- ----------- --------- --------"

  @vcr upstream-update-list.yml
  Scenario: Check for upstream updates and there are some
    When I run "terminus upstream:updates:list [[test_site_name]].dev"
    Then I should get: "------------------------------------------ --------------------- -------------------------------------------------------------------------------------------------------------------------------------------- ---------------------"
    And I should get: "Commit ID                                  Timestamp             Message                                                                                                                                      Author"
    And I should get: "------------------------------------------ --------------------- -------------------------------------------------------------------------------------------------------------------------------------------- ---------------------"
    And I should get: "cccdd26e7c511bebbd40b23e6756056f8eb7bd3d   2016-09-07T19:06:47   Update to WordPress 4.6.1. For more information, see: https://wordpress.org/news/2016/09/wordpress-4-6-1-security-and-maintenance-release/   Pantheon Automation"
    And I should get: "99d9779d7924d37be5750954b774ec786a95e5e0   2016-08-16T20:13:12   Update to WordPress 4.6. For more information, see: https://wordpress.org/news/2016/08/pepper/                                               Pantheon Automation"
    And I should get: "------------------------------------------ --------------------- -------------------------------------------------------------------------------------------------------------------------------------------- ---------------------"
