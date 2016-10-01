Feature: Running Drush Commands on a Drupal Site
  In order to interact with Drupal without configuring Pantheon site aliases
  As a Terminus user
  I want the ability to run arbitrary drush commands in terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr site_environment-info
  Scenario: Running a simple drush command
    When I run: terminus drush [[test_site_name]].dev -- version
    Then I should get: "Terminus is in test mode"
    And I should get: "drush version"

  @vcr site_environment-info
  Scenario: Running a drush command that is permitted by PantheonSSH
    When This step is implemented I will test: a permitted drush command
    When I run: terminus drush [[test_site_name]].dev -- php-eval 'print \"oh happy days\"'
    Then I should get:
    """
    oh happy days
    """

  @vcr site_environment-info
  Scenario: Running a drush command that is not permitted by PantheonSSH
    When This step is implemented I will test: a protected drush command
    When I run: terminus drush [[test_site_name]].dev -- php-eval 'print \"oh happy days\";'
    Then I should get:
    """
    Command not supported as typed
    """
