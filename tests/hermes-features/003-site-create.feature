Feature: Create a site
  In order to use the Pantheon platform
  As a user
  I need to be able to create a site on it.

  Background: I am authenticated
    Given I am authenticated

  Scenario: Creates a new site
    Given a site named "[[test_site_name]]" does not already exist
    When I run "terminus site:create [[test_site_name]] [[test_site_name]] e8fe8550-1ab9-4964-8838-2b9abdccf4bf"
    Then I should get the notice: "Creating a new site..."
    And I should get the notice: "Deploying CMS..."
    And I should get the notice: "Deployed CMS"
    And a site named "[[test_site_name]]" exists

  Scenario: Site creation is prevented if the new site does not have an original slug
    Given a site named "[[test_site_name]]" already exists
    When I run "terminus site:create [[test_site_name]] [[test_site_name]] e8fe8550-1ab9-4964-8838-2b9abdccf4bf"
    Then I should get the error: "The site name [[test_site_name]] is already taken."
