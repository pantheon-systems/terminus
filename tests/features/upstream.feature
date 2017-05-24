Feature: Listing upstreams
  In order to decide on which upstream I can use
  As a user
  I need to be able to list available Pantheon upstreams.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated

  @vcr upstream-list.yml
  Scenario: List all my upstreams
    When I run "terminus upstream:list"
    And I should get: "-------------------------------------- --------------------------------------------- -----------"
    And I should get: "ID                                     Name                                          Framework"
    And I should get: "-------------------------------------- --------------------------------------------- -----------"
    And I should get: "17edd1b6-2c45-4885-9f5e-3e6fb9363dff   Adding an Upstream                            wordpress"
    And I should get: "31bc4254-be20-4e8d-afe6-6c585e58435a   Atrium                                        drupal"
    And I should get: "7e1b9786-7888-4cb0-8da9-d1e180c1363c   Awesome Codebase                              drupal"
    And I should get: "bf703821-4c18-45a1-88b8-3d9ec302273d   Backdrop                                      backdrop"
    And I should get: "3a7df0b5-97d1-4385-99b9-2e2358c60f1d   C suite                                       drupal"
    And I should get: "6413825e-7c23-3549-bbf1-c797251bf6e9   CU Express 3                                  drupal8"
    And I should get: "3b754bc2-48f8-4388-b5b5-2631098d03de   CiviCRM Starter Kit                           drupal"
    And I should get: "8a662dde-53d6-4fdb-8eac-eea9f5848d00   Commerce Kickstart                            drupal"
    And I should get: "d7370d7e-46fb-4b10-b79f-942b5abf51de   DKAN                                          drupal"
    And I should get: "35b0e365-a191-4c70-adbe-9d02d01343f3   Drops 8 Composer                              drupal8"
    And I should get: "974b75c2-4ba7-49f8-8a54-3a45c07dfe02   Drupal 6                                      drupal"
    And I should get: "21e1fada-199c-492b-97bd-0b36b53a9da0   Drupal 7                                      drupal"
    And I should get: "8a129104-9d37-4082-aaf8-e6f31154644e   Drupal 8                                      drupal8"
    And I should get: "6922bfa7-d771-4a39-8e62-052cb1e8dd75   Drupal 8 Pre-Release                          drupal8"
    And I should get: "4d5939ad-ba0b-4dd1-acfb-c0092d5c0320   Drupal 8 SXSW                                 drupal8"
    And I should get: "4fa4f7d1-e341-48f7-9594-4e7c21b9bb68   Drupal8 PHP                                   drupal8"
    And I should get: "4c7176de-e079-eed1-154d-44d5a9945b65   Empty Upstream                                drupal8"
    And I should get: "318ab2ca-80af-46c8-a317-8bca25abc755   Node.js Train Controller                      drupal7"
    And I should get: "4c17f505-05d0-4b79-b38a-0bc548405a10   Open Outreach                                 drupal"
    And I should get: "6eb1ad36-afef-46d7-90d1-3a1bd4296863   Open Restaurant                               drupal"
    And I should get: "2adff196-4672-44c9-af2a-4590963b90d8   OpenAid                                       drupal"
    And I should get: "86112161-4cb2-410f-8bb1-8a1fb4f56dae   OpenIdeaL                                     drupal"
    And I should get: "b459145b-8771-4597-8b84-684a3d93dce0   OpenPublic                                    drupal"
    And I should get: "bc77fa2f-2235-4eec-8e6b-4d69d1cf5908   OpenPublish                                   drupal"
    And I should get: "8ad1efe0-0231-42ae-9520-c96241495b82   Panopoly                                      drupal"
    And I should get: "175cce4f-fa3f-4426-b1a6-e0fae9e19f2e   Panopoly                                      drupal"
    And I should get: "216f85b2-620b-470d-9597-f64ade76dc9a   Plato Típico                                  drupal"
    And I should get: "c9f1311f-4cae-41ca-8276-cca230c25f37   Protected Upstream                            drupal"
    And I should get: "f141b5e0-a614-4294-a86c-6c24df9bf6c5   Pushtape                                      drupal"
    And I should get: "10d6937e-1dd2-4490-9950-11867ba43597   RedHen Raiser                                 drupal"
    And I should get: "10d2f3a5-728a-460f-afa9-dbaee10eef3b   Ruby Test Upstream                            wordpress"
    And I should get: "e44da442-2b1e-639a-f790-edf7cb26c9a0   Rutgers School Website - Wordpress            wordpress"
    And I should get: "64408272-2d4b-614a-753b-334a2baf4263   Rutgers Unit/Department Website - Wordpress   wordpress"
    And I should get: "158e2876-13a4-427f-96cf-d29a3daa538b   Sprowt                                        drupal"
    And I should get: "de858279-cb87-4664-825c-fcb4c2928717   Static HTML                                   unknown"
    And I should get: "e8fe8550-1ab9-4964-8838-2b9abdccf4bf   WordPress                                     wordpress"
    And I should get: "50824beb-9045-5ee8-da2b-ffc80f35ecda   WordPress (dev)                               wordpress"
    And I should get: "e08b604f-9026-d304-f68c-e10e91b58969   WordPress (dev)                               wordpress"
    And I should get: "756477ba-4575-e375-8c23-0639c9a733f0   WordPress + Takeoff Upstream                  wordpress"
    And I should get: "-------------------------------------- --------------------------------------------- -----------"

  @vcr upstream-info.yml
  Scenario: Get info on an upstream
    When I run "terminus upstream:info WordPress"
    Then I should get: "------------- ------------------------------------------------------------------------------"
    And I should get: "ID            11111111-1111-1111-1111-111111111111"
    And I should get: "Name          WordPress"
    And I should get: "Framework     wordpress"
    And I should get: "URL           https://github.com/pantheon-systems/wordpress.git"
    And I should get: "Description   WordPress is web software you can use to create a beautiful website or blog."
    And I should get: "------------- ------------------------------------------------------------------------------"
