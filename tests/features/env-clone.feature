Feature: Cloning site content
  In order to duplicate a site
  As a user
  I need to be able to duplicate a site.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_clone-content
  Scenario: Site Clone Environment
    When I run "terminus --no-ansi env:clone [[test_site_name]].test dev --yes"
    Then I should see a progress bar with the message: 'setting up...'
    Then I should see a progress bar with the message: 'Cloned files from "live" to "dev", cloned database from "live" to "dev"'

  @vcr site_clone-content
  Scenario: Site Clone Files Only
    When I run "terminus --no-ansi env:clone [[test_site_name]].test dev --files-only --yes"
    Then I should see a progress bar with the message: 'setting up...'
    Then I should see a progress bar with the message: 'Cloned files from "live" to "dev"'

  @vcr site_clone-content
  Scenario: Site Clone Files Only
    When I run "terminus --no-ansi env:clone [[test_site_name]].test dev --db-only --yes"
    Then I should see a progress bar with the message: 'setting up...'
    Then I should see a progress bar with the message: 'Cloned database from "live" to "dev"'
