Feature: Listing upstreams
  In order to decide on which upstream I can use
  As a user
  I need to be able to list available Pantheon upstreams.

  @vcr upstreams_list
  Scenario: List Upstreams
    When I run "terminus upstreams list"
    Then I should get:
    """
    WordPress
    """
