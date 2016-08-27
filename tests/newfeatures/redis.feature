Feature: Using Redis
  In order to enhance my site's speed and responsivity
  As a business or an elite user
  I need to be able to manipluate Redis via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_redis_enable
  Scenario: Enabling Redis
    When I run "terminus redis:enable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis enabled. Converging bindings...
    """

  @vcr site_redis_disable
  Scenario: Disabling Redis
    When I run "terminus redis:disable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis disabled. Converging bindings...
    """

