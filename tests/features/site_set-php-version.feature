Feature: Managing the PHP version of sites and environments
  In order to make Pantheon work for my site
  As a user
  I need to be able to change which PHP version my site and environments are using.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_set-php-version
  Scenario: Setting the site's PHP version
    When I run "terminus site set-php-version --site=[[test_site_name]] --version=5.5"
    And I get info for the site "[[test_site_name]]"
    Then I should get: "5.5"

  @vcr site_set-php-version_environment
  Scenario: Setting an environment's PHP version
    When I run "terminus site set-php-version --site=[[test_site_name]] --env=dev --version=5.3"
    And I get info for the "dev" environment of "[[test_site_name]]"
    Then I should get: "5.3"

  @vcr site_set-php-version_environment_unset
  Scenario: Setting an environment's PHP version to the site default
    When I run "terminus site set-php-version --site=[[test_site_name]] --env=dev --version=default"
    And I get info for the "dev" environment of "[[test_site_name]]"
    Then I should get one of the following: "5.3, 5.5"
