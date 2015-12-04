Feature: Site hostname lookup
  In order to locate a site by its hostname
  As a user
  I need to be able to search through all environmental hostnames to find it.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_lookup
  Scenario: Looking up a hostname belonging to [[test_site_name]]
    When I run "terminus site hostnames lookup --hostname=[[test_site_hostname]]"
    Then I should get:
    """
    [[test_site_name]]
    """

  @vcr site_hostnames_lookup_invalid
  Scenario: Failing to look up an invalid hostname
    When I run "terminus site hostnames lookup --hostname=invalid"
    Then I should get:
    """
    Could not locate an environment with the hostname "invalid".
    """
