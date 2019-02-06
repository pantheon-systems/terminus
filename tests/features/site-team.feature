Feature: Managing a site's team
  In order to work collaboratively
  As a user
  I need to be able to alter a site's team membership.

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-team-add.yml
  Scenario: Adding a team member
    When I run "terminus site:team:add [[test_site_name]] [[other_user]] developer"
    And I list the team members on "[[test_site_name]]"
    Then I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"
    And I should get: "First name   Last name   Email                   Role          User ID                                Is owner?"
    And I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"
    And I should get: "Dev          User        devuser@pantheon.io     team_member   11111111-1111-1111-1111-111111111111   true"
    And I should get: "Dev          User        otheruser@pantheon.io   developer     3a1d2042-cca3-432e-94c4-12a8f2b6a950   false"
    And I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"

  @vcr site-team-add-no-change-mgmt.yml
  Scenario: Adding a team member without change management enabled
    When I run "terminus site:team:add [[test_site_name]] [[other_user]] developer"
    Then I should see a warning message: Site does not have change management enabled, defaulting to user role team_member.
    And I list the team members on "[[test_site_name]]"
    Then I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"
    And I should get: "First name   Last name   Email                   Role          User ID                                Is owner?"
    And I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"
    And I should get: "Dev          User        devuser@pantheon.io     team_member   11111111-1111-1111-1111-111111111111   true"
    And I should get: "Dev          User        otheruser@pantheon.io   team_member   3a1d2042-cca3-432e-94c4-12a8f2b6a950   false"
    And I should get: "------------ ----------- ----------------------- ------------- -------------------------------------- -----------"

  @vcr site-team-role.yml
  Scenario: Changing a team member's role
    When I run "terminus site:team:role [[test_site_name]] [[other_user]] admin"
    Then I should get one of the following: "This site does not have its change-management option enabled., Changed a user role"

  @vcr site-team-list.yml
  Scenario: Listing team members
    When I run "terminus site:team:list [[test_site_name]]"
    Then I should see a table with rows like:
    """
      First name
      Last name
      Email
      Role
      User ID
      Is owner?
    """

  @vcr site-team-list-empty.yml
  Scenario: Listing team members when there aren't any
    When I run "terminus site:team:list [[test_site_name]]"
    Then I should get the warning: "[[test_site_name]] has no team members."
    And I should see a table with rows like:
    """
      First name
      Last name
      Email
      Role
      User ID
      Is owner?
    """

  @vcr site-team-remove.yml
  Scenario: Removing a team member
    When I run "terminus site:team:remove [[test_site_name]] [[other_user]]"
    Then I should get:
    """
    Removed a user from site team
    """

  @vcr site-team-remove-self.yml
  Scenario: Removing a team member
    When I run "terminus site:team:remove [[test_site_name]] [[other_user]]"
    Then I should get:
    """
    Removed your user from site team
    """
