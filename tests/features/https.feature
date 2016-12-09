Feature: Set HTTPS Certificate
  In order to enable HTTPS to secure my website
  As a user
  I need to be able to be able update my environment's HTTPS certificate

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr https-add.yml
  Scenario: Set an HTTPS Certificate
    When I run "terminus https:set [[test_site_name]].live fake fake"
    Then I should get:
    """
    Converged loadbalancer
    """

  @vcr https-delete.yml
  Scenario: Delete an HTTPS Certificate
    When I run "terminus https:remove [[test_site_name]].dev"
    Then I should get:
    """
    Converged containers on "dev"
    """

  @vcr https-delete-nocert.yml
  Scenario: Delete a non-existant HTTPS Certificate
    When I run "terminus https:remove [[test_site_name]].dev"
    Then I should get:
    """
    The dev environment does not have https enabled
    """

  @vcr https-info.yml
  Scenario: Retrieve information on an environment's HTTPS setup
    When I run "terminus https:info [[test_site_name]].live"
    Then I should get: "---------- -----------------------------------------"
    And I should get: "Enabled?   true"
    And I should get: "IPv4       161.47.18.130"
    And I should get: "IPv6       2001:4801:7905:0100:aff8:a2d8:0000:0df0"
    And I should get: "---------- -----------------------------------------"


  @vcr https-info-inactive.yml
  Scenario: Retrieve information on an environment's HTTPS setup, which is inactive
    When I run "terminus https:info [[test_site_name]].dev"
    Then I should get: "---------- -------"
    And I should get: "Enabled?   false"
    And I should get: "IPv4"
    And I should get: "IPv6"
    And I should get: "---------- -------"
