Feature: Displaying the Drush versions of sites and environments
  In order to use Drush on my site
  As a user
  I need to be able to see which Drush versions my site and environments are using.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_drush-version
  Scenario: Retrieving the environment's Drush version
    When I run "terminus site drush-version --site=[[test_site_name]] --env=dev"
    Then I should get: "8"
