Feature: Running Drush Commands on a Drupal Site
  In order to interact with Drupal without configuring Pantheon site aliases
  As a Terminus user
  I want the ability to run arbitrary drush commands in terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr drush.yml
  Scenario: Running a simple drush command
    When I run: terminus drush [[test_site_name]].dev -- version
    Then I should get: "Terminus is in test mode"
    And I should get: "drush version"

  @vcr drush.yml
  Scenario: Running a drush command that is not permitted
    When I run: terminus drush [[test_site_name]].dev -- sql-connect
    Then I should see an error message: That command is not available via Terminus. Please use the native drush command.
    Then I should get: "Hint: You may want to try `terminus connection:info --field=mysql_command`."

  @vcr wp.yml
  Scenario: Running a drush command on a Wordpress site is not possible
    When I run: terminus drush [[test_site_name]].dev -- status
    Then I should see an error message: The drush command is only available on sites running drupal, drupal8. The framework for this site is wordpress.
