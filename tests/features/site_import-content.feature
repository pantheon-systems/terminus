Feature: Import site content
  In order to move a site onto Pantheon
  As a user
  I need to be able to import site content.

  @vcr site_import-content_files
  Scenario: Import files into the site
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site import-content --site=[[test_site_name]] --element=files --url=https://s3.amazonaws.com/pantheondemofiles/files.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Import files to "dev"
    """

  @vcr site_import-content_database
  Scenario: Import database into the site
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site import-content --site=[[test_site_name]] --element=database --url=https://s3.amazonaws.com/pantheondemofiles/database.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Importing database to "dev"
    """
