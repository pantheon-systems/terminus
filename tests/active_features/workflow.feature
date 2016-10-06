Feature: View site workflow information
  In order to view workflow information
  As a user
  I need to be able to list data related to them.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr workflows_list
  Scenario: List workflows
    When I run "terminus workflow:list [[test_site_name]]"
    Then I should get:
    """
    Sync code on "dev"
    """

  @vcr workflows_show
  Scenario: Show a specific Workflow's Details and Operations
    When This step is implemented I will test: showing a specific workflow's details
    When I run "terminus workflow:info [[test_site_name]] 11111111-1111-1111-1111-111111111111"
    Then I should get:
    """
    Deploy a CMS (Drupal or Wordpress)
    """
    And I should get:
    """
    Take Screenshot
    """

  @vcr workflows_show
  Scenario: Show the most recent set of logs for a workflow that has logs
    When This step is implemented I will test: Show the most recent set of logs for a workflow that has logs
    When I run "terminus workflow:info [[test_site_name]] --latest-with-logs"
    Then I should see an error message: No recent workflow has logs
