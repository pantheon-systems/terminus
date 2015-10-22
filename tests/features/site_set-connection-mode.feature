Feature: Site set connection mode

  Scenario: Setting connection mode to git
    @vcr site_set-connection-mode_git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=git"
    Then I should get: "."
    And I should get:
    """
    Enable git push mode for "dev"
    """

  Scenario: Setting connection mode to sftp
    @vcr site_set-connection-mode_sftp
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=sftp"
    Then I should get: "."
    And I should get:
    """
    Enabling on-server development via SFTP for "dev"
    """

  Scenario: Failing to set connection mode to invalid mode
    @vcr site_set-connection-mode_git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=invalid"
    Then I should get: "."
    And I should get:
    """
    You must specify the mode as either sftp or git.
    """

  Scenario: Failing to set the connection mode to the current mode
    @vcr site_set-connection-mode_git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=sftp"
    Then I should get:
    """
    The connection mode is already set to sftp.
    """
