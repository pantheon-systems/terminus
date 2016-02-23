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
    When I run "terminus site set-owner --site=[[test_site_name]] --member=[[other_user]]"
    Then I should get:
    """
    Promoted new owner
    """

  @vcr site_set-owner_self
  Scenario: Failing to change the site owner to the current owner
    When I run "terminus site set-owner --site=[[test_site_name]] --member=[[username]]"
    Then I should get:
    """
    The billed member is the owner, promotion disabled.
    """

  @vcr site_set-owner_solo
  Scenario: Failing to change the site owner to the current owner
    Given "[[other_user]]" is not a member of the team on "[[test_site_name]]"
    When I run "terminus site set-owner --site=[[test_site_name]] --member=[[username]]"
    Then I should get:
    """
    The new owner must be added with "terminus site team add-member" before promoting.
    """
