Feature: site

  Scenario: Site Clone Environment
    @vcr site-clone-env
    Given a site named "[[test_site_name]]"
    When I run "terminus site clone-env --site=[[test_site_name]] --from-env=test --to-env=dev --files --yes"
    Then I should get:
    """
    Cloning files ... Working .
    """

  Scenario: Site Connection Mode
    @vcr site-connection-mode
    Given a site named "[[test_site_name]]"
    When I run "terminus site connection-mode --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Git
    """
