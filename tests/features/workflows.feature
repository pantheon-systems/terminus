Feature: View site workflow information
  In order to view workflow information
  As a user
  I need to be able to list data related to them.

  @vcr workflows_list
  Scenario: Site Workflows
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus workflows list --site=[[test_site_name]]"
    Then I should get:
    """
    Converge "dev"
    """
