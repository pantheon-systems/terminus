Feature: Adding hostnames to an environment
  In order to ensure that my site is accessible
  As a user
  I need to be able to affix hostnames to my site's environnments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_add
  Scenario: Adding a hostname to an environment
    When I run "terminus site hostnames add --site=[[test_site_name]] --env=live --hostname=testdomain.com"
    Then I should get: 
    """
    Added testdomain.com to [[test_site_name]]-live
    """
