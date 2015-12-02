Feature: View site workflow information
  In order to view workflow information
  As a user
  I need to be able to list data related to them.

  @vcr workflows_list
  Scenario: List Workflows
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus workflows list --site=[[test_site_name]]"
    Then I should get:
    """
    Converge "dev"
    """

  @vcr workflows_show
  Scenario: Show a specific Workflow's Details and Operations
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus workflows show --site=[[test_site_name]] --workflow_id=4b4bbbc4-4602-11e5-a354-bc764e117665"
    Then I should get:
    """
    Deploy a CMS (Drupal or Wordpress)
    """
    And I should get:
    """
    quicksilver	my_script
    """

  @vcr workflows_logs
  Scenario: Show logs for a specific Workflow
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus workflows logs --site=[[test_site_name]] --workflow_id=4b4bbbc4-4602-11e5-a354-bc764e117665"
    Then I should get:
    """
    lorem log ipsum delor
    """

  @vcr workflows_logs_latest
  Scenario: Show the most recent set of logs for a workflow that has logs
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus workflows logs --site=[[test_site_name]] --latest"
    Then I should get:
    """
    lorem log ipsum delor
    """
