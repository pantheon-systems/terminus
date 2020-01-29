Feature: Cloning site content
  In order to duplicate a site
  As a user
  I need to be able to duplicate a site.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-clone.yml
  Scenario: Clone an environment
    When I run "terminus env:clone-content [[test_site_name]].test dev --yes"
    Then I should get:
    """
    Cloning files from "test" to "dev"
    """
    Then I should get:
    """
    Cloning database from "test" to "dev"
    """

  @vcr env-clone.yml
  Scenario: Clone an environment's files only
    When I run "terminus env:clone-content [[test_site_name]].test dev --files-only --yes"
    Then I should get:
    """
    Cloning files from "test" to "dev"
    """

  @vcr env-clone.yml
  Scenario: Clone an environment's database only
    When I run "terminus env:clone-content [[test_site_name]].test dev --db-only --yes"
    Then I should get:
    """
    Cloning database from "test" to "dev"
    """

  @vcr env-clone-from-uninitialized.yml
  Scenario: Attempting to clone from an uninitialized environment
    When I run "terminus env:clone-content [[test_site_name]].test dev --db-only --yes"
    Then I should get:
    """
    [[test_site_name]]'s test environment cannot be cloned from because it has not been initialized. Please run `env:deploy [[test_site_name]].test` to initialize it.
    """

  @vcr env-clone-to-uninitialized.yml
  Scenario: Attempting to clone to an uninitialized environment
    When I run "terminus env:clone-content [[test_site_name]].test live --db-only --yes"
    Then I should get:
    """
    [[test_site_name]]'s live environment cannot be cloned into because it has not been initialized. Please run `env:deploy [[test_site_name]].live` to initialize it.
    """
