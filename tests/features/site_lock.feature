Feature: site lock

  Scenario: Locking and Unlocking an environment
    @vcr site_lock
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site lock add --site=[[test_site_name]] --env=dev --username=pantheon --password=password"
    Then I should get:
    """
    Creating new lock on behat-tests -> dev
    """
    Then I should get: "."
    Then I should get:
    """
    Locking "dev"
    """

    When I run "terminus site lock remove --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Removing lock from behat-tests -> dev
    """
    Then I should get: "."
    Then I should get:
    """
    Unlocking "dev"
    """

Scenario: Get lock info for an environment
  @vcr site_lock_info
  Given I am authenticated
  And a site named "[[test_site_name]]"
  When I run "terminus site lock info --site=[[test_site_name]] --env=dev"
  Then I should get:
  """
  Locked
  """
