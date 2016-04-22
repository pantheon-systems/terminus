Feature: Using Solr
  In order to enhance my users' ability to search my sites
  As a business or an elite user
  I need to be able to manipluate Solr via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"

  @vcr site_solr_invalid_service_level
  Scenario: Being rejected from Solr functions due to service level
    Given the service level of "[[test_site_name]]" is "free"
    When I run "terminus site solr enable --site=[[test_site_name]]"
    Then I should get:
    """
    You must upgrade to a business or an elite plan to use Solr.
    """

  @vcr site_solr_enable_pro
  Scenario: Enabling Solr
    Given the service level of "[[test_site_name]]" is "pro"
    When I run "terminus site solr enable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr enabled. Converging bindings...
    """

  @vcr site_solr_disable_pro
  Scenario: Disabling Solr
    Given the service level of "[[test_site_name]]" is "pro"
    When I run "terminus site solr disable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr disabled. Converging bindings...
    """

  @vcr site_solr_enable_business
  Scenario: Enabling Solr
    Given the service level of "[[test_site_name]]" is "business"
    When I run "terminus site solr enable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr enabled. Converging bindings...
    """

  @vcr site_solr_disable_business
  Scenario: Disabling Solr
    Given the service level of "[[test_site_name]]" is "business"
    When I run "terminus site solr disable --site=[[test_site_name]]"
    Then I should get:
    """
    Solr disabled. Converging bindings...
    """
