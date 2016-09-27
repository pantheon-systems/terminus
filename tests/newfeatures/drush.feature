Feature: Running Drush Commands on a Drupal Site
  In order to interact with Drupal without configuring Pantheon site aliases
  As a Terminus user
  I want the ability to run arbitrary drush commands in terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  Scenario: Running a simple drush command
    When I run "terminus drush [[test_site_name]].dev -- version"
    Then I should get:
    """
    Drush Version   :  8.1.3
    """

  Scenario: Running a drush command that is permitted by PantheonSSH
    When This step is implemented I will test: a permitted drush command
    When I run "terminus drush [[test_site_name]].dev -- php-eval 'print \"oh happy days\"'"
    Then I should get:
    """
    oh happy days
    """

  Scenario: Running a drush command that is not permitted by PantheonSSH
    When This step is implemented I will test: a protected drush command
    When I run "terminus drush [[test_site_name]].dev -- php-eval 'print \"oh happy days\";'"
    Then I should get:
    """
    Command not supported as typed
    """
