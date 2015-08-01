Feature: Daily Terminus QA Report
  Scenario: Create site
    @create-site
    Given I am authenticated
    And no site named "[[test_site_name]]"
    When I create a "Drupal 7" site named "[[test_site_name]]"
    When I list the sites
    Then I should get:
    """
    [[test_site_name]]
    """

  Scenario: Browser activation
    @activate-site
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I am prompted to "Activate the site in your browser" on "[[test_site_name]]" at "http://dev-[[test_site_name]].onebox.pantheon.io"
    Then I "skipped" the test

  Scenario: Connection mode SFTP
    @connection-mode-sftp
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the connection mode on "[[test_site_name]]" to "sftp"
    And I check the connection mode on "[[test_site_name]]"
    Then I should get:
    """
    Sftp
    """

  Scenario: Install module
    @install-module
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I install the module "ctools" to "[[test_site_name]]"
    Then I should get:
    """
    Project ctools
    """

  Scenario: Get the file diff
    @file-diff
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I am prompted to "Get the file diff. (Just close the tab after it loads.)" on "[[test_site_name]]" at "http://[[dashboard_host]]/sites/[[id]]"
    Then I "skipped" the test

  Scenario: Commit changes via SFTP
    @commit-changes-sftp
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I commit changes to the "dev" environment of "[[test_site_name]]" with message "test commit"
    Then I should get:
    """
    Successfully commited
    """

  Scenario: Initialize test environment
    @init-test-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I initialize the "test" environment on "[[test_site_name]]"
    Then I check the URL "http://test-[[test_site_name]].[[php_site_domain]]" for validity

  Scenario: Initializing the live environment
    @init-live-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I initialize the "live" environment on "[[test_site_name]]"
    Then I check the URL "http://live-[[test_site_name]].[[php_site_domain]]" for validity

  Scenario: Connection mode Git
    @connection-mode-git
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the connection mode on "[[test_site_name]]" to "git"
    And I check the connection mode on "[[test_site_name]]"
    Then I should get:
    """
    Git
    """

  Scenario: Backup dev environment
    @backup-dev-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I back up "all" elements of the "dev" environment of "[[test_site_name]]"
    And I list the backups of the "dev" environment of "[[test_site_name]]"
    Then I should have a new backup

  Scenario: Create multidev environment
    @create-multidev-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I create multidev environment "multidev" from "dev" on "[[test_site_name]]"
    And I check the list of environments on "[[test_site_name]]"
    Then I should get:
    """
    multidev
    """

  Scenario: Commit changes to multidev
    @commit-changes-multidev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I commit changes to the "dev" environment of "[[test_site_name]]" with message "test commit2"
    Then I should get:
    """
    Successfully commited
    """

  Scenario: Merge multidev environment into dev
    @merge-multidev-into-dev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I merge the "multidev" environment into the "dev" environment on "[[test_site_name]]"

  Scenario: Clone live into dev
    @clone-db-files-into-dev
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I clone the "live" environment into the "dev" environment on "[[test_site_name]]"

  Scenario: Deploy test env cloning live
    @deploy-test-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I clone the "live" environment into the "test" environment on "[[test_site_name]]"

  Scenario: Deploying the live environment
    @deploy-live-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I deploy the "live" environment of "[[test_site_name]]"

  Scenario: Clear caches on live
    @clear-caches
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I clear the caches on the "live" environment of "[[test_site_name]]"
    Then I should get:
    """
    Caches cleared
    """

  Scenario: Restoring the dev environment from backup
    @restore-from-backup
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I restore the "dev" environment of "[[test_site_name]]" from backup

  Scenario: Add team member
    @add-team-member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I add "[[other_user]]" to the team on "[[test_site_name]]"
    And I list the team members on "[[test_site_name]]"
    Then I should get:
    """
    [[other_user]]
    """

  Scenario: Remove team member
    @remove-team-member
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I remove "[[other_user]]" from the team on "[[test_site_name]]"
    And I list the team members on "[[test_site_name]]"
    Then I should not get:
    """
    [[other_user]]
    """

  Scenario: Attach instrument to site
    @attach-instrument
    Given I am authenticated
    And a site named "[[test_site_name]]"
    Then I am prompted to "Add a credit card to the site" on "[[test_site_name]]" at "http://[[dashboard_admin_host]]/sites/[[id]]"
    And I attach the instrument of "0" to site "[[test_site_name]]"

  Scenario: Change service level
    @change-service-level
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I set the service level of "[[test_site_name]]" to "basic"
    And I check the service level of "[[test_site_name]]"
    Then I should get:
    """
    Service Level is 'basic'
    """

  Scenario: Add hostname
    @add-hostname
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I add hostname "[[hostname]]" to the "live" environment of "[[test_site_name]]"
    And I list the hostnames on the "live" environment of "[[test_site_name]]"
    Then I should get:
    """
    [[hostname]]
    """
