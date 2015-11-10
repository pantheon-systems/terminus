Feature: View site workflow information
  In order to view workflow information
  As a user
  I need to be able to list data related to them.

  @vcr site_workflows
  Scenario: Site Workflows
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site workflows --site=[[test_site_name]]"
    Then I should get:
    """
    Converge "dev"
    """
