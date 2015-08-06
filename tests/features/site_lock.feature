Feature: site lock

  Scenario: Locking and Unlocking an environment
    @vcr site_lock
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site lock add --site=[[test_site_name]] --env=dev --username=pantheon --password=password"
    Then I should get:
    """
    Creating new lock
    """
    Then I should get:
    """
    Success
    """

    When I run "terminus site lock remove --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Removing lock
    """
    Then I should get:
    """
    Success
    """

Scenario: Get lock info for an environment
  @vcr site_lock_info
  Given I am authenticated
  And a site named "[[test_site_name]]"
  When I run "terminus site lock info --site=[[test_site_name]] --env=dev"
  Then I should get:
  """
  locked
  """
