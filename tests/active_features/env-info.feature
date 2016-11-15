Feature: Displaying environmental information
  In order to access and work with the Pantheon platform
  As a user
  I need to be able to check information on my site's environments.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_environment-info
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

  @vcr site_environment-info
  Scenario: Checking an information field of an environment
    When I run "terminus env:info [[test_site_name]].dev --field=connection_mode"
    Then I should get one of the following: "git, sftp"

  @vcr site_environment-info
  Scenario: Failing to check an invalid field
    When I run "terminus env:info [[test_site_name]].dev --field=invalid"
    Then I should get:
    """
    The requested field, 'invalid', is not defined.
    """

  Scenario: Prompt for environment argument using a number
    When this step is implemented I will test: environment selection interactivity by number
    When I run: terminus env:info [[test_site_name]]
    Then I should get:
    """
    Please choose an environment for this command:
    1) dev
    2) test
    3) live
    4) my-multidev
    Enter site name or number:
    """
    When I enter: 1
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

  Scenario: Prompt for environment argument using a name
    When this step is implemented I will test: environment selection interactivity by name
    When I run: terminus env:info [[test_site_name]]
    Then I should get:
    """
    Please choose an environment for this command:
    1) dev
    2) test
    3) live
    4) my-multidev
    Enter site name or number:
    """
    When I enter: dev
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
