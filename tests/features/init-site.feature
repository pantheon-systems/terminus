Feature: Environment Initializiaton

  Scenario: Initializing the test environment
    @vcr site-init-env
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site init-env --site=[[test_site_name]] --env=test"
    #Then I check the URL "http://test-[[test_site_name]].[[php_site_domain]]" for validity
