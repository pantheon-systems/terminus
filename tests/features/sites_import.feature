Feature: Create a site and import its content
  In order to quickly move sites onto Pantheon
  As a user
  I need to be able to import content into a new site.

  Background: I am authenticated
    Given I am authenticated

  @vcr sites_import_new
  Scenario: Import files and database into a new site
    Given no site named "[[test_site_name]]"
    When I run "terminus sites import --site=[[test_site_name]] --label=[[test_site_name]] --url=https://s3.amazonaws.com/pantheon-infrastructure/testing/canary.tgz"
    Then I should get:
    """
    Created new site "[[test_site_name]]"
    """
    Then I should get "."
    And I should get:
    """
    Importing database/files to "dev"
    """

  @vcr sites_import_duplicate
  Scenario: Failing to import files and database into an existing site
    Given a site named "[[test_site_name]]"
    When I run "terminus sites import --site=[[test_site_name]] --label=[[test_site_name]] --url=https://s3.amazonaws.com/pantheon-infrastructure/testing/canary.tgz --yes"
    Then I should get:
    """
    A site named [[test_site_name]] already exists.
    """
