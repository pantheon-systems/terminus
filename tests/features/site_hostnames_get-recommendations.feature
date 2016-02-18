Feature: Environment DNS lookup
  In order to use a domain on a Pantheon site
  As a user
  I need to be able to list the hostname recommendations for an environment.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_dns-recommendations
  Scenario: Looking up the DNS recommendations for [[test_site_name]]
    When I run "terminus site hostnames get-recommendations --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    [[test_site_hostname]]
    """
