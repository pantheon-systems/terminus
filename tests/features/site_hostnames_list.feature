Feature: Listing an environment's hostnames
  In order to ensure that my site is accessible
  As a user
  I need to be able to list all hostnames attached to the environnments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_list
  Scenario: Listing all hostnames belonging to an environment
    When I run "terminus site hostnames list --site=[[test_site_name]] --env=live"
    Then I should get: 
    """
    live-[[test_site_name]].[[php_site_domain]]
    """
