Feature: sites

  Scenario: List Sites
    @vcr sites-list
    When I run "terminus sites list"
    Then I should get:
    """
    Name
    """

  Scenario: Create Site
    @vcr sites-create
    When I run "terminus sites create --site=[[test_site_name]] --label=[[test_site_name]] --product=WordPress"
    And I run "terminus sites list"
    Then I should get:
    """
    [[test_site_name]]
    """

  Scenario: Delete Site
    @vcr sites-delete
    When I run "terminus sites delete --site=[[test_site_name]] --yes"
    And I run "terminus sites list"
    Then I should not get:
    """
    [[test_site_name]]
    """

  #Scenario: Create Site From Import
    ##@vcr sites-create-from-import
    #Given I am authenticating
    #When I run "terminus sites import --site=[[test_site_name]] --url=https://pantheon-infrastructure.s3.amazonaws.com/testing/drush_archive_josh_and_rina.tar.gz"
    #Then I should get:
    #"""
    #Name
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

