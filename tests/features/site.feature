Feature: Site Commands
  In order to view site information
  As a user
  I need to be able to list data related to it.

  @vcr site-info
  Scenario: Site Info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site info --site=[[test_site_name]]"
    Then I should get:
    """
    Service Level
    """

  @vcr site-workflows
  Scenario: Site Workflows
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site workflows --site=[[test_site_name]]"
    Then I should get:
    """
    Converge "dev"
    """
