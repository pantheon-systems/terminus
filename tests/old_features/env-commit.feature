Feature: Committing code to an environment's branch
  In order to maintain my git repository for my site
  As a user
  In need to be able to commit changes made to the site on the server

  Background: I am authenticated and have a site called [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr env-commit.yml
  Scenario: Committing a change
    When I run "terminus env:commit [[test_site_name]].dev --message='Behat test commit'"
    Then I should get: "There is no code to commit."
    When I run "terminus env:code-log [[test_site_name]].dev"
    Then I should get: "--------------------- ---------- ----------------- ------------------------------------------ -------------------"
    And I should get: "Timestamp             Author     Labels            Commit ID                                  Message"
    And I should get: "--------------------- ---------- ----------------- ------------------------------------------ -------------------"
    And I should get: "2016-08-18T23:31:15   Dev User   dev               a6a94cff8dbc3f15c93b2d3c6777aa334a476927   Behat test commit"
    And I should get: "2016-08-17T00:46:03   Dev User   dev               41710101cf34dd3980c7534a2a1678208303fe19   Removed README.md"
    And I should get:
    """
    2016-08-16T22:09:25   Root       test, live, dev   1fdf194d3d7a0c930a4f118e1398412765320328   "Initial Commit"
    """
    And I should get: "--------------------- ---------- ----------------- ------------------------------------------ -------------------"
