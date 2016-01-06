Feature: Set HTTPS Certificate
  In order to enable HTTPS to secure my website
  As a user
  I need to be able to be able update my environment's HTTPS certificate

  @vcr site_set-https-certificate
  Scenario: Set an HTTPS Certificate
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site set-https-certificate --site=[[test_site_name]] --env=live --certificate=fake --private_key=fake"
    Then I should get:
    """
    Converged loadbalancer
    """
