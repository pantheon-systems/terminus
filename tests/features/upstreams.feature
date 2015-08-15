Feature: upstreams

  Scenario: List Upstreams
    @vcr upstreams-list
    When I run "terminus upstreams list"
    Then I should get:
    """
    WordPress
    """
