Feature: Environmental information

  Scenario: Checking environmental information
    @vcr site_environment-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    dev
    """

  Scenario: Checking an information field of an environment
    @vcr site_environment-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  Scenario: Failing to check an invalid field
    @vcr site_environment-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site environment-info --site=[[test_site_name]] --env=dev --field=invalid"
    Then I should get:
    """
    There is no such field.
    """

