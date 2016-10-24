Feature: Set HTTPS Certificate
  In order to enable HTTPS to secure my website
  As a user
  I need to be able to be able update my environment's HTTPS certificate

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr https_delete.yml
  Scenario: Delete an HTTPS Certificate
    When I run "terminus https:delete [[test_site_name]].dev"
    Then I should get:
    """
    Converged containers on "dev"
    """

  @vcr https_delete-nocert.yml
  Scenario: Delete a non-existant HTTPS Certificate
    When I run "terminus https:delete [[test_site_name]].dev"
    Then I should get:
    """
    The dev environment does not have https enabled
    """

