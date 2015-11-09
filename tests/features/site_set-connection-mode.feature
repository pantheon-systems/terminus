Feature: Set a site's connection mode
  In order to ensure the correct sort of connectivity for my site
  As a user
  I need to be able to change my site's connection mode.

  @vcr site_set-connection-mode_git
  Scenario: Setting connection mode to git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=git"
    Then I should get: "."
    And I should get:
    """
    Enable git push mode for "dev"
    """

  @vcr site_set-connection-mode_sftp
  Scenario: Setting connection mode to sftp
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=sftp"
    Then I should get: "."
    And I should get:
    """
    Enabling on-server development via SFTP for "dev"
    """

  @vcr site_set-connection-mode_git
  Scenario: Failing to set connection mode to invalid mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=invalid"
    Then I should get: "."
    And I should get:
    """
    You must specify the mode as either sftp or git.
    """

  @vcr site_set-connection-mode_git
  Scenario: Failing to set the connection mode to the current mode
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-connection-mode --site=[[test_site_name]] --env=dev --mode=sftp"
    Then I should get:
    """
    The connection mode is already set to sftp.
    """
