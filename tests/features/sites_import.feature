Feature: Site import

  Scenario: Import files and database into a new site
    @vcr sites_import_new
    Given I am authenticated
    And no site named "[[test_site_name]]"
    When I run "terminus sites import --site=[[test_site_name]] --label=[[test_site_name]] --url=https://s3.amazonaws.com/pantheon-infrastructure/testing/canary.tgz"
    Then I should get:
    """
    Created new site "[[test_site_name]]"
    """
    Then I should get "."
    Then I should get:
    """
    Importing database/files to "dev"
    """

  Scenario: Failing to import files and database into an existing site
    @vcr sites_import_duplicate
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites import --site=[[test_site_name]] --label=[[test_site_name]] --url=https://s3.amazonaws.com/pantheon-infrastructure/testing/canary.tgz --yes"
    Then I should get:
    """
    A site named [[test_site_name]] already exists.
    """
