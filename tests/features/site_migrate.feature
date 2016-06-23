Feature: Migrate a site onto Pantheon
  In order to move a site onto Pantheon
  As a user
  I need to be able to migrate its content.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_migrate
  Scenario: Migrating a site archive onto Pantheon
    When I run "terminus site migrate --site=[[test_site_name]] --url=https://s3.amazonaws.com/pantheondemofiles/archive.tar.gz"
    Then I should get "."
    Then I should get:
    """
    Migrated site onto Pantheon
    """
