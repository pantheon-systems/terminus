Feature: Create a new backup for a site
  In order to secure my site against failures
  As a user
  I need to be able to create a new backup of my site

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr backup-create.yml
  Scenario: Create a new backup of the entire environment
    When I run "terminus backup:create [[test_site_name]].dev"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Created a backup of the dev environment
    """

  @vcr backup-create.yml
  Scenario: Create a new backup of a specific element of the environment
    When I run "terminus backup:create [[test_site_name]].dev --element=database"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Created a backup of the dev environment
    """

  @vcr backup-create.yml
  Scenario: Create a new backup of the environment with a specific preservation
    When I run "terminus backup:create [[test_site_name]].dev --keep-for=90"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Created a backup of the dev environment
    """
