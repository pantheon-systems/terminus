Feature: Environment Initializiaton

  Scenario: Initializing the test environment
    @vcr site-init-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site init-env --site=[[test_site_name]] --env=test"
    Then I should get "."
    Then I should get:
    """
    Deploying code to "test", and cloning files from "dev", and cloning database from "dev"
    """

  Scenario: Should not allow re-initializing an environment
    @vcr site-init-env-already-initialized
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site init-env --site=[[test_site_name]] --env=test"
    Then I should get:
    """
    The test environment has already been initialized
    """
