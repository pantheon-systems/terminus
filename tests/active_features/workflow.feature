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
    When I run "terminus workflow:info [[test_site_name]] --workflow-id=11111111-1111-1111-1111-111111111111"
    Then I should get:
    """
    Deploy a CMS (Drupal or Wordpress)
    """
    And I should get:
    """
    Take Screenshot
    """

  @vcr workflows_show
  Scenario: Show a specific Workflow's status
    When I run "terminus workflow:info:status [[test_site_name]] --workflow-id=11111111-1111-1111-1111-111111111111"
    Then I should get:
    """
    Deploy a CMS (Drupal or Wordpress)
    """
    And I should get:
    """
    succeeded
    """

  @vcr workflows_show
  Scenario: Show a specific Workflow's operations
    When I run "terminus workflow:info:operations [[test_site_name]] --workflow-id=11111111-1111-1111-1111-111111111111"
    Then I should get:
    """
    Apply any hostname changes
    """
    And I should get:
    """
    Take Screenshot
    """

  @vcr workflows_show
  Scenario: Try show a Workflow that has no logs
    When I run "terminus workflow:info:logs [[test_site_name]] --workflow-id=11111111-1111-1111-1111-111111111111"
    Then I should see a notice message: Workflow operations did not contain any logs.

  @vcr workflows_show
  Scenario: Show the most recent set of logs for a workflow that has logs
    When This step is implemented I will test: Show the most recent set of logs for a workflow that has logs
    When I run "terminus workflow:info:logs [[test_site_name]] --latest-with-logs"
    Then I should get:
    """
    My logs here
    """

