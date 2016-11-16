Feature: Using Redis
  In order to enhance my site's speed and responsivity
  As a business or an elite user
  I need to be able to manipluate Redis via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr redis-enable.yml
  Scenario: Enabling Redis
    When I run "terminus redis:enable [[test_site_name]]"
    Then I should get: "Redis enabled. Converging bindings."
    And I should get: "Brought environments to desired configuration state"

  @vcr redis-disable.yml
  Scenario: Disabling Redis
    When I run "terminus redis:disable [[test_site_name]]"
    Then I should get: "Redis disabled. Converging bindings."
    And I should get: "Brought environments to desired configuration state"

