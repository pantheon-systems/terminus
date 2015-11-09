Feature: Wipe the content in a site's environment
  In order to remove all site content
  As a user
  I need to be able to wipe a site container of its contents.

  @vcr site-wipe
  Scenario: Wipe Environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site wipe --site=[[test_site_name]] --env=dev --yes"
    Then I should get:
    """
    Successfully wiped [[test_site_name]]-dev
    """
