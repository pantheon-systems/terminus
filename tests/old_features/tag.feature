Feature: Tagging organizational sites
  In order to organize and categorize sites
  As a user
  I need to be able to apply tags to those sites.

  Background: Given I am authenticated and have a site belonging to an organization
    Given I am authenticated
    And a site named "[[test_site_name]]" belonging to "[[organization_name]]"

  @vcr tag-add.yml
  Scenario: Adding a tag
    When I run "terminus tag:add [[test_site_name]] '[[organization_name]]' testtag"
    Then I should get: "[[organization_name]] has tagged [[test_site_name]] with testtag."

  @vcr tag-list.yml
  Scenario: Listing a site's tags
    When I run "terminus tag:list [[test_site_name]] '[[organization_name]]'"
    Then I should get: "- testtag"

  @vcr tag-remove.yml
  Scenario: Removing a tag
    When I run "terminus tag:remove [[test_site_name]] '[[organization_name]]' testtag"
    Then I should get: "[[organization_name]] has removed the testtag tag from [[test_site_name]]."

  @vcr tag-site-list-empty.yml
  Scenario: Failing to list a site's tags because it hasn't any
    When I run "terminus tag:list [[test_site_name]] '[[organization_name]]'"
    Then I should get: "[[organization_name]] does not have any tags for [[test_site_name]]."
    And I should get: "{  }"
