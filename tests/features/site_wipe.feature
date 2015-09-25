Feature: Wipe content in a Site's Environment

  Scenario: Wipe Environment
    @vcr site-wipe
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site wipe --site=[[test_site_name]] --env=dev --yes"
    Then I should get:
    """
    Successfully wiped [[test_site_name]]-dev
    """
