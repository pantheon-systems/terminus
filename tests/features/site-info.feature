Feature: View site information
  In order to view site information
  As a user
  I need to be able to list data related to it.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-info.yml
  Scenario: Site Info
    When I run "terminus site:info [[test_site_name]]"
    Then I should see a table with rows like:
    """
      ID
      Name
      Label
      Created
      Framework
      Organization
      Service Level
      Upstream
      PHP Version
      Holder Type
      Holder ID
      Owner
      Date Last Frozen
    """

  @vcr site-info-owner.yml
  Scenario: Site info for a specific field
    When I run "terminus site:info [[test_site_name]] --field=id"
    Then I should get: "11111111-1111-1111-1111-111111111111"

