Feature: Listing upstreams
  In order to decide on which upstream I can use
  As a user
  I need to be able to list available Pantheon upstreams.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated

  @vcr upstreams_list
  Scenario: List Upstreams
    When I run "terminus upstream:list"
    Then I should get:
    """
    WordPress
    """
