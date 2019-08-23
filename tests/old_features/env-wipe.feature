Feature: Wipe the content in a site's environment
  In order to remove all site content
  As a user
  I need to be able to wipe a site container of its contents.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-wipe.yml
  Scenario: Wipe Environment
    When I run "terminus env:wipe [[test_site_name]].dev  --yes"
    Then I should get:
    """
    Wiping the "dev" environment of "[[test_site_name]]"
    """
    And I should get:
    """
    Wiped files and database in "dev"
    """
