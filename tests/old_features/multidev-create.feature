Feature: Working with multidev environments
  In order to work collaboratively on Pantheon
  As a user
  I need to be able to create, remove, and alter multidev environments.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr multidev-create.yml
  Scenario: Create a multidev environment
    When I run "terminus multidev:create [[test_site_name]].dev multidev"
    Then I should get:
    """
    Creating Multidev environment "multidev"
    """
