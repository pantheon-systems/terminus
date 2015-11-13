Feature: Update sites with all their upstream's updates
  In order to easily maintain my sites
  As a user
  I need to be able to update all my sites to reflect the current upstream.

  @vcr sites_mass-update
  Scenario: Mass-Update Sites
    Given I am authenticated
    When I run "terminus sites mass-update --yes"
    Then I should get one of the following: "Backing up, Needs update, No sites in need of updating."

  @vcr sites_mass-update_filtered
  Scenario: Filtering mass-update list by tag
    Given I am authenticated
    When I run "terminus sites mass-update --tag=tag --org=[[enterprise_org_name]] --yes"
    Then I should get:
    """
    No sites in need of updating.
    """
