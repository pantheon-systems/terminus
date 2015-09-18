Feature: Multidev environments

  Scenario: Create a multidev environment
    @vcr site_create-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the service level of "[[test_site_name]]" is "business"
    When I run "terminus site create-env --site=[[test_site_name]] --from-env=dev --to-env=multidev"
    Then I should get: "."
    And I should get:
    """
    Creating Multidev environment "multidev"
    """

  Scenario: Fail to create necessary environment
    @vcr site_create-env_bad_name
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the service level of "[[test_site_name]]" is "business"
    When I run "terminus site create-env --site=[[test_site_name]] --from-env=dev --to-env=dev"
    Then I should get:
    """
    Environment name "dev" is reserved by Pantheon and cannot becreated into a Multidev environment.
    """

  Scenario: Fail to create extant environment
    @vcr site_create-env_duplicate
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the service level of "[[test_site_name]]" is "business"
    When I run "terminus site create-env --site=[[test_site_name]] --from-env=dev --to-env=multidev"
    Then I should get:
    """
    The environment "multidev" already exists.
    """

  Scenario: Fail to create environment due to service level
    @vcr site_create-env_unauthorized
    Given I am authenticated
    And a site named "[[test_site_name]]"
    And the service level of "[[test_site_name]]" is "basic"
    When I run "terminus site create-env --site=[[test_site_name]] --from-env=dev --to-env=multidev"
    Then I should get:
    """
    This site does not have the authority to conduct this operation.
    """

  #Scenario: Delete a multidev environment
    #@vcr site_delete-env
    #Given I am authenticated
    #And a site named "[[test_site_name]]"
    #And the service level of "[[test_site_name]]" is "business"
    #When I run "terminus site delete-env--site=[[test_site_name]] --from-env=dev --to-env=multidev"
    #Then I should get:
      #"""
      #"""
    
  #Scenario: Fail to delete a required environment
    #@vcr site_delete-env_bad_name
    #Given I am authenticated
    #And a site named "[[test_site_name]]"
    #And the service level of "[[test_site_name]]" is "business"
    #When I run "terminus site delete-env --site=[[test_site_name]] --env=dev"
    #Then I should get:
      #"""
      #"""
    
  #Scenario: Fail to delete an environment due to service level
    #@vcr site_delete-env_unauthorized
    #Given I am authenticated
    #And a site named "[[test_site_name]]"
    #And the service level of "[[test_site_name]]" is "basic"
    #When I run "terminus site delete-env --site=[[test_site_name]] --env=dev"
    #Then I should get:
      #"""
      #"""
