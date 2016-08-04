Feature: Import a site onto Pantheon
  In order to move a site onto Pantheon
  As a user
  I need to be able to import its content.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_import
  Scenario: Importing a site archive onto Pantheon
    When I run "terminus site import --site=[[test_site_name]] --url=https://s3.amazonaws.com/pantheondemofiles/archive.tar.gz --yes"
    Then I should get "."
    Then I should get:
    """
    Imported site onto Pantheon
    """
