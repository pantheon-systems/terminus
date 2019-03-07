Feature: Listing sites
  In order to administer my sites
  As a user
  I need to be able to list those sites.

  Background: I am authenticated
    Given I am authenticated

  @vcr site-list-empty.yml
  Scenario: Listing a user's sites when they haven't any
    When I run "terminus site:list --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "You have no sites."
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"
    And I should get: "Name   ID   Plan   Framework   Owner   Created   Memberships   Is Frozen?"
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"

  @vcr site-list.yml
  Scenario: Listing a user's sites
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --owner=me --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Plan      Framework   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "behat-tests   11111111-1111-1111-1111-111111111111   Sandbox   wordpress   11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --name=[[test_site_name]] --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Plan      Framework   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "behat-tests   11111111-1111-1111-1111-111111111111   Sandbox   wordpress   11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --name=[[test_site_name]]"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Plan      Framework   Region   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "behat-tests   11111111-1111-1111-1111-111111111111   Sandbox   wordpress   chios    11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --upstream=e8fe8550-1ab9-4964-8838-2b9abdccf4bf"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Plan      Framework   Region   Owner                                  Created               Memberships                                  Is Frozen?"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "behat-tests   11111111-1111-1111-1111-111111111111   Sandbox   wordpress   chios    11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------- -------------------------------------- --------------------- -------------------------------------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by name, excluding the test site
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --name=missing --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "You have no sites."
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"
    And I should get: "Name   ID   Plan   Framework   Owner   Created   Memberships   Is Frozen?"
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by plan name and it's empty
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --plan=basic --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "You have no sites."
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"
    And I should get: "Name   ID   Plan   Framework   Owner   Created   Memberships   Is Frozen?"
    And I should get: "------ ---- ------ ----------- ------- --------- ------------- ------------"

  @vcr site-list.yml
  Scenario: Filter sites list by plan name with results
    Given a site named "[[test_site_name]]"
    When I run "terminus site:list --plan=sandbox --fields=name,id,plan_name,framework,owner,created,memberships,frozen"
    Then I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "Name          ID                                     Plan      Framework   Owner                                  Created               Memberships                                  Is Frozen?"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
    And I should get: "behat-tests   11111111-1111-1111-1111-111111111111   Sandbox   wordpress   11111111-1111-1111-1111-111111111111   2016-08-16 22:09:01   11111111-1111-1111-1111-111111111111: Team   false"
    And I should get: "------------- -------------------------------------- --------- ----------- -------------------------------------- --------------------- -------------------------------------------- ------------"
