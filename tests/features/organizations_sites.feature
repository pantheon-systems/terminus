Feature: organizations sites

  Scenario: List Organization Sites
    @vcr organization-sites
    Given I am authenticated
    When I run "terminus organizations sites --org=34b1ba6e-d59e-489b-9179-9121722a1bc1"
    Then I should get:
    """
    enterprise-site-yo
    """
