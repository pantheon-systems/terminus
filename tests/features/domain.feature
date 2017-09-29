Feature: Adding domains to an environment
  In order to ensure that my site is accessible
  As a user
  I need to be able to manage domains attached to my site's environnments

  Background: I am authenticated and have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr domain-add.yml
  Scenario: Adding a domain to an environment
    When I run "terminus domain:add [[test_site_name]].live testdomain.com"
    Then I should get:
    """
    Added testdomain.com to [[test_site_name]].live
    """

  @vcr domain-remove.yml
  Scenario: Removing a domain from an environment
    When I run "terminus domain:remove [[test_site_name]].live testdomain.com"
    Then I should get:
    """
    Removed testdomain.com from [[test_site_name]].live
    """

  @vcr domain-list.yml
  Scenario: Listing all domains belonging to an environment
    When I run "terminus domain:list [[test_site_name]].live"
    Then I should get: "--------------------------------- ---------- --------------"
    And I should get: "Domain/ID                          Type       Is Deletable"
    And I should get: "---------------------------------- ---------- --------------"
    And I should get: "testdomain.com                     custom     true"
    And I should get: "live-[[test_site_name]].[[php_site_domain]]   platform   false"
    And I should get: "---------------------------------- ---------- --------------"

  @vcr domain-lookup.yml
  Scenario: Looking up a domain belonging to [[test_site_name]]
    When I run "terminus domain:lookup dev-[[test_site_name]].onebox.pantheon.io"
    Then I should get: "This operation may take a long time to run."
    And I should get: "---------------- --------------------------------------"
    And I should get: "Site ID          11111111-1111-1111-1111-111111111111"
    And I should get: "Site Name        [[test_site_name]]"
    And I should get: "Environment ID   dev"
    And I should get: "---------------- --------------------------------------"

  @vcr domain-lookup.yml
  Scenario: Failing to look up an invalid domain
    When I run "terminus domain:lookup invalid"
    Then I should get: "This operation may take a long time to run."
    And I should get:
    """
    Could not locate an environment with the domain invalid.
    """

  @vcr domain-dns.yml
  Scenario: Looking up the DNS recommendations for [[test_site_name]]
    When I run "terminus domain:dns [[test_site_name]].dev"
    Then I should get: "-------------------- ------------- ---------------------------------- ---------------------------------- -------- ------------------------"
    And I should get: "Domain               Record Type   Recommended Value                  Detected Value                     Status   Status Message"
    And I should get: "-------------------- ------------- ---------------------------------- ---------------------------------- -------- ------------------------"
    And I should get: "www.[[test_site_name]].me   CNAME         live-[[test_site_name]].[[php_site_domain]]   live-[[test_site_name]].[[php_site_domain]]   okay     Correct value detected"
    And I should get: "[[test_site_name]].me       A             23.185.0.2                         23.185.0.2                         okay     Correct value detected"
    And I should get: "[[test_site_name]].me       AAAA          2620:12a:8000::2                   2620:12a:8000::2                   okay     Correct value detected"
    And I should get: "[[test_site_name]].me       AAAA          2620:12a:8001::2                   2620:12a:8001::2                   okay     Correct value detected"
    And I should get: "-------------------- ------------- ---------------------------------- ---------------------------------- -------- ------------------------"

