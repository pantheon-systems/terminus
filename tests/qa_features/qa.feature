Feature: Daily Terminus QA Report
  @create-site
  Scenario: Create site
    Given I am authenticated
    And no site named "[[test_site_name]]"
    When I create a "Drupal 7" site named "[[test_site_name]]"
    When I list the sites
    Then I should get:
    """
    [[test_site_name]]
    """

  @activate-site
  Scenario: Browser activation
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I activate the Drupal site "[[test_site_name]]"
    Then I "skipped" the test

  @connection-mode-sftp
  Scenario: Connection mode SFTP
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the connection mode on "[[test_site_name]]" to "sftp"
    And I check the connection mode on "[[test_site_name]]"
    Then I should get:
    """
    Sftp
    """

  @install-module
  Scenario: Install module
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I install the module "ctools" to "[[test_site_name]]"
    Then I should get:
    """
    Project ctools
    """

  @file-diff
  Scenario: Get the file diff
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I am prompted to "Get the file diff. (Just close the tab after it loads.)" on "[[test_site_name]]" at "http://[[dashboard_host]]/sites/[[id]]"
    Then I "skipped" the test

  @commit-changes-sftp
  Scenario: Commit changes via SFTP
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I commit changes to the "dev" environment of "[[test_site_name]]" with message "test commit"
    Then I should get:
    """
    Successfully commited
    """

  @init-test-env
  Scenario: Initialize test environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I initialize the "test" environment on "[[test_site_name]]"
    Then I check the URL "http://test-[[test_site_name]].[[php_site_domain]]" for validity

  @init-live-env
  Scenario: Initializing the live environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I initialize the "live" environment on "[[test_site_name]]"
    Then I check the URL "http://live-[[test_site_name]].[[php_site_domain]]" for validity

  @connection-mode-git
  Scenario: Connection mode Git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the connection mode on "[[test_site_name]]" to "git"
    And I check the connection mode on "[[test_site_name]]"
    Then I should get:
    """
    Git
    """

  @backup-dev-env
  Scenario: Backup dev environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I back up "all" elements of the "dev" environment of "[[test_site_name]]"
    And I list the backups of the "dev" environment of "[[test_site_name]]"
    Then I should have a new backup

  @attach-instrument
  Scenario: Attach instrument to site
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And a payment insturment with uuid "[[payment_instrument_uuid]]"
    When I attach the instrument "[[payment_instrument_uuid]]" to site "[[test_site_name]]"
    Then I should get:
    """
    [[payment_instrument_uuid]]
    """

  @change-service-level
  Scenario: Change service level
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the service level of "[[test_site_name]]" to "business"
    And I check the service level of "[[test_site_name]]"
    Then I should get:
    """
    Service Level is 'business'
    """

  @create-multidev-env
  Scenario: Create multidev environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I create multidev environment "multidev" from "dev" on "[[test_site_name]]"
    And I check the list of environments on "[[test_site_name]]"
    Then I should get:
    """
    multidev
    """

  @commit-changes-multidev
  Scenario: Commit changes to multidev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I commit changes to the "dev" environment of "[[test_site_name]]" with message "test commit2"
    Then I should get:
    """
    Successfully commited
    """

  @merge-multidev-into-dev
  Scenario: Merge multidev environment into dev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I merge the "multidev" environment into the "dev" environment on "[[test_site_name]]"

  @clone-db-files-into-dev
  Scenario: Clone live into dev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I clone the "live" environment into the "dev" environment on "[[test_site_name]]"

  @deploy-test-env
  Scenario: Deploy test env cloning live
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I deploy the "live" environment from "test" of "[[test_site_name]]" with the message "test deploy"
    Then I should get:
    """
    Woot! Code deployed to test
    """

  @deploy-live-env
  Scenario: Deploying the live environment
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I deploy the "live" environment from "test" of "[[test_site_name]]" with the message "test deploy2"
    Then I should get:
    """
    Woot! Code deployed to live
    """

  @clear-cache
  Scenario: Clear caches on live
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I clear the caches on the "live" environment of "[[test_site_name]]"
    Then I should get:
    """
    Caches cleared
    """

  @restore-from-backup
  Scenario: Restoring the dev environment from backup
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I restore the "dev" environment of "[[test_site_name]]" from backup

  @add-team-member
  Scenario: Add team member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I add "[[other_user]]" to the team on "[[test_site_name]]"
    And I list the team members on "[[test_site_name]]"
    Then I should get:
    """
    [[other_user]]
    """

  @remove-team-member
  Scenario: Remove team member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I remove "[[other_user]]" from the team on "[[test_site_name]]"
    And I list the team members on "[[test_site_name]]"
    Then I should not get:
    """
    [[other_user]]
    """

  @add-hostname
  Scenario: Add hostname
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I add hostname "[[hostname]]" to the "live" environment of "[[test_site_name]]"
    And I list the hostnames on the "live" environment of "[[test_site_name]]"
    Then I should get:
    """
    [[hostname]]
    """
