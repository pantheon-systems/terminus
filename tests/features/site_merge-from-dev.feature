Feature: site merge-from-dev

  Scenario: Merge the Dev Environment into a Multidev Environment
    @vcr site_merge-from-dev
    Given I am authenticated
    When I run "terminus site merge-from-dev --site=[[test_site_name]] --env=stuff"
    Then I should get:
    """
    Merged the Dev environment into the stuff environment
    """
