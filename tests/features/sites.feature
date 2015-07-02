Feature: sites
  In order to develop
  As a UNIX user
  I need to be able to see a list of pantheon sites

  Scenario: List sites
    @vcr list-sites
    Given Terminus is authenticating
    When I run "terminus sites show"
    Then I should get:
    """
    Name
    """

  Scenario: Create sites
    @vcr create-sites
    Given Terminus is authenticating
    When I run "terminus sites create --name='behat-test' --label='Behat Test' --org=None --product=WordPress"
    Then I should get:
    """
    Creating new WordPress installation
    """
    And I run "terminus site info --site=behat-test"
    Then I should get:
    """
    wordpress
    """

  Scenario: Site backups
    @vcr site-backups
    Given Terminus is authenticating
    When I run "terminus site backup get --site=behat-test --env=dev --element=code 2>&1"
    Then I should get:
    """
    No backups available.
    """

  Scenario: Delete sites
    @vcr delete-sites
    Given Terminus is authenticating
    When I run "terminus sites delete --site=behat-test --yes"
    Then I should get:
    """
    Deleting behat-test
    """
