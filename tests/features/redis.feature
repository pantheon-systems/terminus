Feature: Using Redis
  In order to enhance my site's speed and responsivity
  As a business or an elite user
  I need to be able to manipluate Redis via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr redis-enable.yml
  Scenario: Enabling Redis
    When I run "[[executable]] redis:enable [[test_site_name]]"
    Then I should get: "Enabling cacheserver for site"

  @vcr redis-disable.yml
  Scenario: Disabling Redis
    When I run "[[executable]] redis:disable [[test_site_name]]"
    Then I should get: "Disabling cacheserver for site"
