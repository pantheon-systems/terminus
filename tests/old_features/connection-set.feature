Feature: Set a site's connection mode
  In order to ensure the correct sort of connectivity for my site
  As a user
  I need to be able to change my site's connection mode.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr connection-set-git.yml
  Scenario: Setting connection mode to git
    When I run "terminus connection:set [[test_site_name]].dev git"
    Then I should see a notice message: Enabling git push mode for "dev"

  @vcr connection-set-sftp.yml
  Scenario: Setting connection mode to sftp
    When I run "terminus connection:set [[test_site_name]].dev sftp"
    Then I should see a notice message: Enabling on-server development via SFTP for "dev"

  @vcr connection-set-sftp-uncommitted-changes.yml
  Scenario: Setting connection mode to sftp
    When I run "terminus connection:set [[test_site_name]].dev git -y"
    Then I should get: "This environment has uncommitted changes which will be lost by changing its connection mode. If you wish to save these changes, use `terminus env:commit [[test_site_name]].dev`."
    And I should see a notice message: Enabling on-server development via SFTP for "dev"

  @vcr connection-set-git.yml
  Scenario: Failing to set the connection mode to the current sftp mode
    # Note: The VCR fixture has the environment in sftp mode to start. Want a given like:
    # Given the [[test_site_name]].dev environment is in the sftp connection mode
    When I run "terminus connection:set [[test_site_name]].dev sftp"
    Then I should see a notice message: The connection mode is already set to sftp.

  @vcr connection-set-sftp.yml
  Scenario: Failing to set the connection mode to the current git mode
    # Note: The VCR fixture has the environment in git mode to start. Want a given like:
    # Given the [[test_site_name]].dev environment is in the git connection mode
    When I run "terminus connection:set [[test_site_name]].dev git"
    Then I should see a notice message: The connection mode is already set to git.

  @vcr connection-set-sftp.yml
  Scenario: Attempting to set connection mode to an invalid mode
    When I run "terminus connection:set [[test_site_name]].dev invalid"
    Then I should see an error message: You must specify the mode as either sftp or git.
