Feature: Adding a primary domain to an environment
  In order to redirect all visitors to my preferred domain
  As a user
  I need to be able to manage primary domains attached to my site's environments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr domain-primary-add.yml
  Scenario: Adding a primary domain to an environment
    When I run "terminus domain:primary:add [[test_site_name]].live testdomain.com"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Set testdomain.com as primary for [[test_site_name]].live
    """

  @vcr domain-primary-add.yml
  Scenario: Removing a primary domain from an environment
    When I run "terminus domain:primary:remove [[test_site_name]].live"
    Then I should get "."
    And I should get "."
    Then I should get:
    """
    Primary domain has been removed from [[test_site_name]].live
    """
