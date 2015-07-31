Feature: sites

  Scenario: List Sites
    @vcr sites-list
    Given I am authenticated
    When I run "terminus sites list"
    Then I should get:
    """
    Name
    """

  Scenario: Delete Site
    @vcr sites-delete
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus sites delete --site=[[test_site_name]] --yes"
    Then I should get:
    """
    Deleted [[test_site_name]]!
    """

  #Scenario: Create Site From Import
    #@vcr sites-create-from-import
    #Given I am authenticating
    #When I run "terminus sites create-from-import --site=[[test_site_name]] --label=[[test_site_name]] --url=https://pantheon-infrastructure.s3.amazonaws.com/testing/canary2.tgz"
    #And I run "terminus sites list"
    #Then I should get:
    #"""
    #[[test_site_name]]
    #"""

  #Scenario: List Aliases
    #@vcr sites-aliases
    #When I run "terminus sites aliases --print"
    #Then I should get:
    #"""
    #[[test_site_name]].dev
    #"""

  Scenario: Mass-Update Sites
    @vcr sites-mass-update
    When I run "terminus sites mass-update"
    Then I should not get:
    """
    Needs update
    """

