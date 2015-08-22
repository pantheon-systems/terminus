Feature: site merge-to-dev

  Scenario: Merge Multidev to dev environment
    @vcr site_merge-to-dev
    Given I am authenticated
    When I run "terminus site merge-to-dev --site=[[test_site_name]] --env=stuff"
    Then I should get: "."
    Then I should get:
    """
    Merged the stuff environment into dev
    """
