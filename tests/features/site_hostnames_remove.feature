Feature: Adding hostnames to an environment
  In order to ensure that my site is accessible
  As a user
  I need to be able to remove hostnames from my site's environnments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_remove
  Scenario: Removing a hostname from an environment
    When I run "terminus site hostnames remove --site=[[test_site_name]] --env=live --hostname=testdomain.com"
    Then I should get: 
    """
    Deleted testdomain.com from [[test_site_name]]-live
    """
