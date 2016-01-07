Feature: Site DNS recommendations
  In order to point my domains at my Pantheon site
  As a user
  I need to be able to access the recommended DNS settings for those domains.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_hostnames_get-recommended
  Scenario: Checking the recommended hostnames
    When I run "terminus site hostnames get-dns --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    [[test_site_name]].com
    """

  @vcr site_hostnames_get-recommended_free
  Scenario: Failing to check the recommended hostnames because the site is free
    When I run "terminus site hostnames get-dns --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    To add custom domains, this site must be upgraded to a paid plan.
    """
