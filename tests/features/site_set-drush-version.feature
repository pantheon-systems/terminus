Feature: Managing the Drush versions of sites and environments
  In order to use Drush on my site
  As a user
  I need to be able to change which Drush version my site and environments are using.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_set-drush-version
  Scenario: Setting the environment's Drush version
    When I run "terminus site set-drush-version --site=[[test_site_name]] --env=dev --version=5"
    Then I should get: "Set dev's Drush version to 5"
