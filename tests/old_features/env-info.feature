Feature: Displaying environmental information
  In order to access and work with the Pantheon platform
  As a user
  I need to be able to check information on my site's environments.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-info.yml
  Scenario: Checking environmental information
    When I run "terminus env:info [[test_site_name]].dev"
    Then I should see a table with rows like:
    """
      ID
      Created
      Domain
      Locked
      Initialized
      Connection Mode
      PHP Version
    """

  @vcr env-info.yml
  Scenario: Checking environmental information
    When I set the environment variable "TERMINUS_SITE" to "[[test_site_name]]"
    And I set the environment variable "TERMINUS_ENV" to "dev"
    And I run "terminus env:info"
    Then I should see a table with rows like:
    """
      ID
      Created
      Domain
      Locked
      Initialized
      Connection Mode
      PHP Version
    """

  @vcr env-info.yml
  Scenario: Checking an information field of an environment
    When I run "terminus env:info [[test_site_name]].dev --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  @vcr env-info.yml
  Scenario: Checking an information field of an environment
    When I set the environment variable "TERMINUS_SITE" to "[[test_site_name]]"
    And I set the environment variable "TERMINUS_ENV" to "dev"
    And I run "terminus env:info --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  @vcr env-info.yml
  Scenario: Failing to check an invalid field
    When I run "terminus env:info [[test_site_name]].dev --field=invalid"
    Then I should get:
    """
    The requested field, 'invalid', is not defined.
    """
