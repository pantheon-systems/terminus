Feature: Running WP-CLI Commands on a Drupal Site
  In order to interact with Drupal without configuring Pantheon site aliases
  As a Terminus user
  I want the ability to run arbitrary WP-CLI commands in terminus

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named: [[test_site_name]]

  @vcr wp.yml
  Scenario: Running a simple WP-CLI command
    When I run: terminus wp [[test_site_name]].dev -- cli version
    Then I should get: "Terminus is in test mode"
    And I should get: "wp cli version"

  @vcr drush.yml
  Scenario: Running a WP-CLI command on a Drupal site is not possible
    When I run: terminus wp [[test_site_name]].dev -- cli version
    Then I should see an error message: The wp command is only available on sites running wordpress, wordpress_network. The framework for this site is drupal8.
