Feature: Set a site's upstream
  In order to cut down on the amount of maintenance work I do
  As a user
  I need to be able to change the upstream of a site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-upstream-set.yml
  Scenario: Changing the upstream of a site and seeing the reversal command
    When I run "terminus site:upstream:set [[test_site_name]] 'Drupal 6' -v -y"
    Then I should get: "To undo this change run `terminus site:upstream:set [[user_id]]"
    And I should get: "Set upstream for [[test_site_name]] to Drupal 6"

  @vcr site-upstream-set-wrong-framework.yml
  Scenario: Failing to change the upstream of a site because the upstream's framework does not match the site's
    When I run "terminus site:upstream:set [[test_site_name]] 'WordPress' -v -y"
    Then I should get: "The site cannot be switched to the 'WordPress' upstream because the upstream framework (wordpress) does not match the site framework. (drupal)"

  @vcr site-upstream-set-upstream-dne.yml
  Scenario: Failing to change the upstream of a site because the requested upstream cannot be found
    When I run "terminus site:upstream:set [[test_site_name]] invalid -v -y"
    Then I should get: "Could not find an upstream identified by invalid."

  @vcr site-upstream-set-unauthorized.yml
  Scenario: Failing to change the upstream of a site because the user is not authorized to make that change
    When I run "terminus site:upstream:set [[test_site_name]] 'WordPress' -v -y"
    Then I should get: "You do not have permission to change the upstream of this site."
