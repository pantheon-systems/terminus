Feature: Listing upstreams
  In order to decide on which upstream I can use
  As a user
  I need to be able to list available organization upstreams.

  Background: I am logged in
    Given I am authenticated

  @vcr org-upstream-list.yml
  Scenario: List my organization's core and custom upstreams
    When I run "terminus org:upstream:list the-upstreamers"
    Then I should get: "-------------------------------------- ------------------------ ------------------------ -------- -----------"
    And I should get: "ID                                     Name                     Machine Name             Type     Framework"
    And I should get: "-------------------------------------- ------------------------ ------------------------ -------- -----------"
    And I should get: "4fa4f7d1-e341-48f7-9594-4e7c21b9bb68   Drupal8 PHP              drupal8-php7             custom   drupal8"
    And I should get: "1f3b2569-b4f6-43ca-a8ae-11c38d90778e   Inaccessible Upstream    inaccessible-upstream    custom   wordpress"
    And I should get: "595003f1-e82e-4af8-ac18-65d09da2f6c5   Mindgrub Test            mindgrub-test            custom   drupal"
    And I should get: "66665092-2d26-47c8-b7c3-a920a37fb805   My Demo Upstream         my-demo-upstream         custom   drupal8"
    And I should get: "745bfede-0557-42ea-a115-246e3b60e8e0   My Upstream for Demo     my-upstream-for-demo     custom   drupal8"
    And I should get: "ba06828d-9507-4217-b7ed-7acb7f9812cf   Pivate Github Upstream   pivate-github-upstream   custom   wordpress"
    And I should get: "c9f1311f-4cae-41ca-8276-cca230c25f37   Protected Upstream       protected-upstream       custom   drupal"
    And I should get: "3a821c48-63b1-4e29-b0f7-8d6a5cafb953   Pubsub Test 2            pubsub-test-2            custom   drupal8"
    And I should get: "5de11c12-e5aa-4125-bc07-b0e14b1fd112   Ronan's Drops 8          ronans-drops-8-sfz       custom   drupal8"
    And I should get: "2375d5c6-8dc2-467f-836b-2ddab05ebe28   Ronan's Drops 8          ronans-drops-8           custom   drupal8"
    And I should get: "-------------------------------------- ------------------------ ------------------------ -------- -----------"
