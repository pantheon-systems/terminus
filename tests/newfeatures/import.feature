Feature: Import a a site and its content onto Pantheon
  In order to move a site onto Pantheon
  As a user
  I need to be able to import its content.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_import
  Scenario: Importing a site archive onto Pantheon
    When I run "terminus import [[test_site_name]] https://s3.amazonaws.com/pantheondemofiles/archive.tar.gz --yes"
    Then I should get "."
    Then I should get:
    """
    Imported site onto Pantheon
    """

  @vcr site_import-content_files
  Scenario: Import files into the site
    When I run "terminus import:files --site=[[test_site_name]] --url=https://s3.amazonaws.com/pantheondemofiles/files.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Importing files to "dev"
    """

  @vcr site_import-content_database
  Scenario: Import database into the site
    When I run "terminus import:database --site=[[test_site_name]] --url=https://s3.amazonaws.com/pantheondemofiles/database.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Importing database to "dev"
    """
