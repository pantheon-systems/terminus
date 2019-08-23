Feature: Looking up a site
  In order to know whether a site exists
  As a user
  I need to be able to detect if a site of a given name already exists

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated

  @vcr site-lookup.yml
  Scenario: Site look-up
    Given I have a site named "[[test_site_name]]"
    When I run "terminus site:lookup [[test_site_name]]"
    Then I should get a valid UUID

  @vcr site-lookup-dne.yml
  Scenario: Site look-up fails because site DNE
    Given I have no site named "invalid"
    When I run "terminus site:lookup invalid"
    Then I should get the error "Could not locate a site your user may access identified by invalid."
    And I should not get a valid UUID
