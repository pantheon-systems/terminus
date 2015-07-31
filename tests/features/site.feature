Feature: site

  Scenario: Site Info
    @vcr site-info
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site info --site=[[test_site_name]]"
    Then I should get:
    """
    service_level
    """
    And I should get:
    """
    sftp://
    """
    And I should get:
    """
    git://
    """

  Scenario: Site Clone Environment
    @vcr site-clone-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site clone-env --site=[[test_site_name]] --from-env=test --to-env=dev --files --yes"
    Then I should get:
    """
    Cloning files ... Working .
    """

  Scenario: Site Connection Mode
    @vcr site-connection-mode
    Given I am authenticated
    Given a site named "[[test_site_name]]"
    When I run "terminus site connection-mode --site=[[test_site_name]] --env=dev"
    Then I should get:
    """
    Git
    """
