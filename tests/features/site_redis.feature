Feature: Using Redis
  In order to enhance my site's speed and responsivity
  As a business or an elite user
  I need to be able to manipluate Redis via Terminus.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[enterprise_org_uuid]]"

  @vcr site_redis_invalid_service_level
  Scenario: Being rejected from Redis functions due to service level
    Given the service level of "[[test_site_name]]" is "free"
    When I run "terminus site redis enable --site=[[test_site_name]]"
    Then I should get:
    """
    You must upgrade to a business or an elite plan to use Redis.
    """

  @vcr site_redis_enable_pro
  Scenario: Enabling Redis
    Given the service level of "[[test_site_name]]" is "pro"
    When I run "terminus site redis enable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis enabled. Converging bindings...
    """

  @vcr site_redis_disable_pro
  Scenario: Disabling Redis
    Given the service level of "[[test_site_name]]" is "pro"
    When I run "terminus site redis disable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis disabled. Converging bindings...
    """

  @vcr site_redis_enable_business
  Scenario: Enabling Redis
    Given the service level of "[[test_site_name]]" is "business"
    When I run "terminus site redis enable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis enabled. Converging bindings...
    """

  @vcr site_redis_disable_business
  Scenario: Disabling Redis
    Given the service level of "[[test_site_name]]" is "business"
    When I run "terminus site redis disable --site=[[test_site_name]]"
    Then I should get:
    """
    Redis disabled. Converging bindings...
    """
