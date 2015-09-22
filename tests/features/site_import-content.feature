Feature: Site content import

  Scenario: Import files into the site
    @vcr site_import-content_files
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site import-content --site=[[test_site_name]] --element=files --url=https://s3.amazonaws.com/pantheondemofiles/files.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Import files to "dev"
    """

  Scenario: Import database into the site
    @vcr site_import-content_database
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site import-content --site=[[test_site_name]] --element=database --url=https://s3.amazonaws.com/pantheondemofiles/database.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Importing database to "dev"
    """
