Feature: Working with multidev environments
  In order to work collaboratively on Pantheon
  As a user
  I need to be able to create, remove, and alter multidev environments.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr multidev-create.yml
  Scenario: Create a multidev environment
    When I run "[[executable]] multidev:create [[test_site_name]].dev multidev"
    Then I should get:
    """
    Creating Multidev environment "multidev"
    """

  @vcr multidev-create-no-db.yml
  Scenario: Create a multidev environment
    When I run "[[executable]] multidev:create [[test_site_name]].dev multidev --no-db"
    Then I should get:
    """
    Creating Multidev environment "multidev"
    """

  @vcr multidev-create-no-files.yml
  Scenario: Create a multidev environment
    When I run "[[executable]] multidev:create [[test_site_name]].dev multidev --no-files"
    Then I should get:
    """
    Creating Multidev environment "multidev"
    """
