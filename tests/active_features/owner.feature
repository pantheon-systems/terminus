Feature: Set a site's owner
  In order to ensure that my site is being managed by the appropriate people
  As a user
  I need to be able to change the owner of my site.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site_set-owner
  Scenario: Changing the site's owner to another team member
    Given "[[other_user]]" is a member of the team on "[[test_site_name]]"
    When I run "terminus owner:set [[test_site_name]] [[other_user]]"
    Then I should get:
    """
    Promoted new owner
    """

  @vcr site_set-owner_solo
  Scenario: Failing to change the site owner when there is only one team member
    Given "[[other_user]]" is not a member of the team on "[[test_site_name]]"
    When I run "terminus owner:set [[username]] --site=[[test_site_name]]"
    When This step is implemented I will test: terminus import:files
    Then I should get:
    """
    The new owner must be added with "terminus site team add-member" before promoting.
    """
