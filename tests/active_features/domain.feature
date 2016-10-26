Feature: Adding domains to an environment
  In order to ensure that my site is accessible
  As a user
  I need to be able to manage domains attached to my site's environnments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_add
  Scenario: Adding a hostname to an environment
    When I run "terminus domain:add testdomain.com --site=[[test_site_name]] --env=live"
    Then I should get: 
    """
    Added testdomain.com to [[test_site_name]]-live
    """

  @vcr site_hostnames_remove
  Scenario: Removing a hostname from an environment
    When I run "terminus domain:remove testdomain.com --site=[[test_site_name]] --env=live"
    Then I should get: 
    """
    Deleted testdomain.com from [[test_site_name]]-live
    """

  @vcr site_hostnames_list
  Scenario: Listing all hostnames belonging to an environment
    When I run "terminus domain:list --site=[[test_site_name]] --env=live"
    Then I should get: 
    """
    live-[[test_site_name]].[[php_site_domain]]
    """

  @vcr site_hostnames_lookup
  Scenario: Looking up a hostname belonging to [[test_site_name]]
    When I run "terminus domain:lookup dev-[[test_site_name]].[[php_site_domain]]"
    Then I should get:
    """
    [[test_site_name]]
    """

  @vcr site_hostnames_lookup_invalid
  Scenario: Failing to look up an invalid hostname
    When I run "terminus domain:lookup invalid"
    Then I should get:
    """
    Could not locate an environment with the hostname "invalid".
    """

  @vcr site_hostnames_get-recommendations
  Scenario: Looking up the DNS recommendations for [[test_site_name]]
    When I run "terminus domain:get-recommendations --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    dev-[[test_site_name]].[[php_site_domain]]
    """
