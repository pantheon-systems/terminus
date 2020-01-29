Feature: View site workflow information
  In order to view workflow information
  As a user
  I need to be able to list data related to them.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr workflow-list.yml
  Scenario: List workflows
    When I run "terminus workflow:list [[test_site_name]]"
    Then I should see a table with the headers: Workflow ID, Environment, Workflow, User, Status, Started At, Finished At, Time Elapsed

  @vcr workflow-list-empty.yml
  Scenario: List workflows when none have been run
    When I run "terminus workflow:list [[test_site_name]]"
    Then I should see a table with the headers: Workflow ID, Environment, Workflow, User, Status, Started At, Finished At, Time Elapsed
    And I should get the warning: "No workflows have been run on [[test_site_name]]."

  @vcr workflow-info-status.yml
  Scenario: Show a specific Workflow's status
    When I run "terminus workflow:info:status [[test_site_name]] --id=11111111-1111-1111-1111-111111111111"
    Then I should see a table with rows like:
    """
    Workflow ID
    Environment
    Workflow
    User
    Status
    Started At
    Finished At
    Time Elapsed
    """

  @vcr workflow-info-status.yml
  Scenario: Show a specific Workflow's operations
    When I run "terminus workflow:info:operations [[test_site_name]] --id=11111111-1111-1111-1111-111111111111"
    Then I should see a table with the headers: Type, Operation, Description, Result, Duration

  @vcr workflow-info-status.yml
  Scenario: Try show a Workflow that has no logs
    When I run "terminus workflow:info:logs [[test_site_name]] --id=11111111-1111-1111-1111-111111111111"
    Then I should see a notice message: Workflow operations did not contain any logs.

  @vcr quicksilver-workflow-info-status.yml
  Scenario: Show the most recent set of logs for a workflow that has logs
    When I run "terminus workflow:info:logs [[test_site_name]]"
    Then I should get: "Showing latest workflow on [[test_site_name]]."
    And I should get: "Simple Quicksilver Example finished in"
    And I should get: "Quicksilver Debugging Output"

