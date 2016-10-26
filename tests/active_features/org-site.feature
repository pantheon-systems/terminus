Feature: Organization sites
  In order to associate sites with organizations
  As an organizational user
  I need to be able to list and edit organizational site memberships.

  Background: I am authenticated
    Given I am authenticated

  @vcr organizations_sites_list
  Scenario: List an organization's sites
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:list '[[organization_name]]'"
    Then I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- ------"
    And I should get: "Name          ID                                     Service Level   Framework   Owner                                  Created               Tags"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- ------"
    And I should get: "[[test_site_name]]   1fdf3bf6-50e3-42d8-ae56-fb4051481404   free            wordpress   11111111-1111-1111-1111-111111111111   2016-08-23 20:38:54"
    And I should get: "------------- -------------------------------------- --------------- ----------- -------------------------------------- --------------------- ------"

  @vcr organizations_sites_add
  Scenario: Add a site to an organization
    Given a site named "[[test_site_name]]"
    When I run "terminus org:site:add '[[organization_name]]' [[test_site_name]]"
    Then I should get: "[[test_site_name]] has been added to the [[organization_name]] organization."

  @vcr organizations_sites_remove
  Scenario: Remove a site from an organization
    Given a site named "[[test_site_name]]" belonging to "[[organization_name]]"
    When I run "terminus org:site:remove '[[organization_name]]' [[test_site_name]]"
    Then I should get: "[[test_site_name]] has been removed from the [[organization_name]] organization."
