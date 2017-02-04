Feature: Listing sites
  In order to administer my sites
  As a user
  I need to be able to list those sites.

  Background: I am authenticated
    Given I am authenticated

  @vcr site-list-empty.yml
  Scenario: Listing a user's sites when they haven't any
    When I run "terminus site:list"
    Then I should get: "You have no sites."
    And I should get: "------ ---- --------------- ----------- ------- --------- ------------- ------------"
    And I should get: "Name   ID   Service Level   Framework   Owner   Created   Memberships   Is Frozen?"
    And I should get: "------ ---- --------------- ----------- ------- --------- ------------- ------------"

  @vcr site-list.yml
  Scenario: Listing a user's sites
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --owner=me"
    Then I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Service Level   Framework   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "[[test_site_name]]   11111111-1111-1111-1111-111111111111   free            wordpress   11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --name=[[test_site_name]]"
    Then I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Service Level   Framework   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "[[test_site_name]]   11111111-1111-1111-1111-111111111111   free            wordpress   11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name, excluding the test site
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --name=missing"
    Then I should get: "You have no sites."
    And I should get: "------ ---- --------------- ----------- ------- --------- ------------- ------------"
    And I should get: "Name   ID   Service Level   Framework   Owner   Created   Memberships   Is Frozen?"
    And I should get: "------ ---- --------------- ----------- ------- --------- ------------- ------------"
