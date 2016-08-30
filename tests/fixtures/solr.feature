Feature: Using Solr
  In order to enhance my users' ability to search my sites
  As a business or an elite user
  I need to be able to manipluate Solr via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[organization_name]]"

  @vcr site_solr_enable
  Scenario: Enabling Solr
    When I run "terminus solr:enable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr enabled. Converging bindings...
    """

  @vcr site_solr_disable
  Scenario: Disabling Solr
    When I run "terminus solr:disable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr disabled. Converging bindings...
    """

