Feature: Import a a site and its content onto Pantheon
  In order to move a site onto Pantheon
  As a user
  I need to be able to import its content.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr import.yml
  Scenario: Importing a site archive onto Pantheon
    When I run "terminus import:site [[test_site_name]] https://s3.amazonaws.com/pantheondemofiles/archive.tar.gz --yes"
    Then I should get: "Imported site onto Pantheon"

  @vcr import-files.yml
  Scenario: Import files into the site
    When I run "terminus import:files [[test_site_name]].dev https://s3.amazonaws.com/pantheondemofiles/files.tar.gz --yes"
    And I should get: "Imported files to [[test_site_name]].dev."

  @vcr import-database.yml
  Scenario: Import database into the site
    When I run "terminus import:database [[test_site_name]].dev https://s3.amazonaws.com/pantheondemofiles/database.tar.gz --yes"
    Then I should get: "Imported database to [[test_site_name]].dev."
