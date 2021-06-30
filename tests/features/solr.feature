Feature: Using Solr
  In order to enhance my users' ability to search my sites
  As a business or an elite user
  I need to be able to manipluate Solr via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[organization_name]]"

  @vcr solr-enable.yml
  Scenario: Enabling Solr
    When I run "[[executable]] solr:enable [[test_site_name]]"
    Then I should get: "Enabling indexserver for site"

  @vcr solr-disable.yml
  Scenario: Disabling Solr
    When I run "[[executable]] solr:disable [[test_site_name]]"
    Then I should get: "Disabling indexserver for site"
