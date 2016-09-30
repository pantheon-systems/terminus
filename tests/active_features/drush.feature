Feature: Running Drush Commands on a Drupal Site
  In order to interact with Drupal without configuring Pantheon site aliases
  As a Terminus user
  I want the ability to run arbitrary drush commands in terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr remote-drush.yml
  Scenario: Running a simple drush command
    When I run: terminus drush [[test_site_name]].dev -- version
    Then I should get: "Terminus is in test mode"
    And I should get: "drush version"

  @vcr remote-drush.yml
  Scenario: Running a drush command that is not permitted
    When I run: terminus drush [[test_site_name]].dev -- sql-connect
    Then I should get: "That command is not available via Terminus. Please use the native drush command."
    Then I should get: "Hint: You may want to try `terminus connection:info --field=mysql_command`."
