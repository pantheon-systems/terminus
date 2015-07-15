Feature: products

  Scenario: List Products
    @vcr products-list
    When I run "terminus products list"
    Then I should get:
    """
    WordPress
    """
