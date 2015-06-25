Feature: sites
  In order to develop
  As a UNIX user
  I need to be able to see a list of pantheon sites

  Scenario: List sites
    Given I am in directory "/"
    When I run "terminus sites show"
    Then I should get:
    """
    phpunittest
    """

  Scenario: Create sites
    Given I am in directory "/"
    When I run "terminus sites create --name='behat-test' --label='behattest' --org='d59379eb-0c23-429c-a7bc-ff51e0a960c2' --product='e8fe8550-1ab9-4964-8838-2b9abdccf4bf'"
    Then I should get:
    """
    Creating new WordPress installation
    """
    And I run "terminus site info --bash --site=behat-test"
    Then I should get:
    """
    wordpress
    """

  Scenario: Site backups
    Given I am in directory "/"
    When I run "terminus site backup get --site=behat-test --env=dev --element=code 2>&1"
    Then I should get:
    """
    No backups available.
    """

  Scenario: Delete sites
    Given I am in directory "/"
    When I run "terminus sites delete --site=behat-test --yes"
    Then I should get:
    """
    Deleting behat-test
    """
