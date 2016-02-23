Feature: Locking and unlocking a site
  In order to quickly take down my site in an emergency
  As a user
  I need to be able to lock and unlock the site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_lock
  Scenario: Locking and Unlocking an environment
    When I run "terminus site lock add --site=[[test_site_name]] --env=dev --username=pantheon --password=password"
    Then I should get:
    """
    Creating new lock on [[test_site_name]]-dev
    """
    Then I should get: "."
    Then I should get:
    """
    Locking "dev"
    """

    When I run "terminus site lock remove --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Removing lock from [[test_site_name]]-dev
    """
    Then I should get: "."
    Then I should get:
    """
    Unlocking "dev"
    """

  @vcr site_lock_info
  Scenario: Get lock info for an environment
    When I run "terminus site lock info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Locked
    """
