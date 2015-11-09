Feature: Finding member items in a collection
  
  @vcr collection_get_failure
  Scenario: Failure to find a member item in a collection
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=invalid --mode=sftp"
    Then I should get:
    """
    Could not find environment "invalid"
    """
