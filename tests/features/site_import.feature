Feature: site import

  Scenario: Import code and/or content into the site
    @vcr site_import
    Given I am authenticated
    And a site named "[[test_site_name]]"
    When I run "terminus site import --site=[[test_site_name]] --element=all --url=https://s3.amazonaws.com/pantheon-infrastructure/testing/canary.tgz"
    Then I should get:
    """
    Import started, you can now safely kill this script without interfering.
    """
    Then I should get "."
    Then I should get:
    """
    Imported database/files to "dev"
    """
