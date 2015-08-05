Feature: site

  Scenario: Site Clone Environment
    @vcr site-clone-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site clone-env --site=[[test_site_name]] --from-env=test --to-env=dev --files --db --yes"
    Then I should get:
    """
    Cloning database
    """
    Then I should get:
    """
    Cloning files
    """
    Then I should get:
    """
    Clone complete!
    """
