Feature: Site mass-update

  Scenario: Mass-Update Sites
    @vcr sites-mass-update
    Given I am authenticated
    When I run "terminus sites mass-update --yes"
    Then I should get one of the following: "Backing up, Needs update, No sites in need of updating."

  Scenario: Filtering mass-update list by tag
    @vcr sites_mass-update_filtered
    Given I am authenticated
    When I run "terminus sites mass-update --tag=tag --org=[[enterprise_org_name]] --yes"
    Then I should get:
    """
    No sites in need of updating.
    """
