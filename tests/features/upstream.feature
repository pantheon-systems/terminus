Feature: Listing upstreams
  In order to decide on which upstream I can use
  As a user
  I need to be able to list available Pantheon upstreams.

  Background: I am logged in and have a site named [[test_site_name]]
    Given I am authenticated

  @vcr upstream-list.yml
  Scenario: List all my upstreams
    When I run "terminus upstream:list"
    Then I should get: "-------------------------------------- --------------------------------------------- ------------ ------------- -----------"
    And I should get: "ID                                     Name                                          Category     Type          Framework"
    And I should get: "-------------------------------------- --------------------------------------------- ------------ ------------- -----------"
    And I should get: "e8fe8550-1ab9-4964-8838-2b9abdccf4bf   WordPress                                     vanilla      core          wordpress"
    And I should get: "bc77fa2f-2235-4eec-8e6b-4d69d1cf5908   OpenPublish                                   publishing   product       drupal"
    And I should get: "10d6937e-1dd2-4490-9950-11867ba43597   RedHen Raiser                                 non-profit   product       drupal"
    And I should get: "b459145b-8771-4597-8b84-684a3d93dce0   OpenPublic                                    government   product       drupal"
    And I should get: "7e1b9786-7888-4cb0-8da9-d1e180c1363c   Awesome Codebase                              general      custom        drupal"
    And I should get: "f141b5e0-a614-4294-a86c-6c24df9bf6c5   Pushtape                                      publishing   product       drupal"
    And I should get: "6922bfa7-d771-4a39-8e62-052cb1e8dd75   Drupal 8 Pre-Release                          vanilla      core          drupal8"
    And I should get: "2adff196-4672-44c9-af2a-4590963b90d8   OpenAid                                       general      product       drupal"
    And I should get: "175cce4f-fa3f-4426-b1a6-e0fae9e19f2e   Panopoly                                      general      product       drupal"
    And I should get: "4c17f505-05d0-4b79-b38a-0bc548405a10   Open Outreach                                 non-profit   product       drupal"
    And I should get: "8ad1efe0-0231-42ae-9520-c96241495b82   Panopoly                                      general      product       drupal"
    And I should get: "3a7df0b5-97d1-4385-99b9-2e2358c60f1d   C suite                                       other        product       drupal"
    And I should get: "8a662dde-53d6-4fdb-8eac-eea9f5848d00   Commerce Kickstart                            commerce     product       drupal"
    And I should get: "583ecb51-ab8e-4a05-93ff-5aa79b11302b   Kickstart                                     vanilla      custom        drupal"
    And I should get: "60484a01-2b74-4bc1-8a63-a9bfb16dcfdf   FreakPress                                    general      custom        wordpress"
    And I should get: "a9591171-63d7-4c5a-9007-199f5d643dbf   OpenScholar                                   education    product       drupal"
    And I should get: "4d5939ad-ba0b-4dd1-acfb-c0092d5c0320   Drupal 8 SXSW                                 general      custom        drupal8"
    And I should get: "10d2f3a5-728a-460f-afa9-dbaee10eef3b   Ruby Test Upstream                            general      product       wordpress"
    And I should get: "de858279-cb87-4664-825c-fcb4c2928717   Static HTML                                   general      custom        unknown"
    And I should get: "8a129104-9d37-4082-aaf8-e6f31154644e   Drupal 8                                      vanilla      core          drupal8"
    And I should get: "6eb1ad36-afef-46d7-90d1-3a1bd4296863   Open Restaurant                               commerce     product       drupal"
    And I should get: "d0da49ff-01b3-4351-b53a-a2625b0f8976   Mukurtu CMS                                   general      product       drupal"
    And I should get: "35b0e365-a191-4c70-adbe-9d02d01343f3   Drops 8 Composer                              general      core          drupal8"
    And I should get: "e4f28d25-c429-4a8c-a6fd-a82e0dd301a2   EasyFlow                                      other        product       unknown"
    And I should get: "31bc4254-be20-4e8d-afe6-6c585e58435a   Atrium                                        non-profit   product       drupal"
    And I should get: "bf703821-4c18-45a1-88b8-3d9ec302273d   Backdrop                                      vanilla      core          backdrop"
    And I should get: "3b754bc2-48f8-4388-b5b5-2631098d03de   CiviCRM Starter Kit                           other        product       drupal"
    And I should get: "1df1bc7d-d4c8-4a1d-bba8-3abced800af0   Community Media Starter Kit                   publishing   development   drupal"
    And I should get: "d7370d7e-46fb-4b10-b79f-942b5abf51de   DKAN                                          general      product       drupal"
    And I should get: "974b75c2-4ba7-49f8-8a54-3a45c07dfe02   Drupal 6                                      vanilla      core          drupal"
    And I should get: "c9a20c50-ffae-4e07-924f-fc7608cd884a   EC LMS                                        general      core          drupal"
    And I should get: "86112161-4cb2-410f-8bb1-8a1fb4f56dae   OpenIdeaL                                     other        product       drupal"
    And I should get: "216f85b2-620b-470d-9597-f64ade76dc9a   Plato Típico                                  general      product       drupal"
    And I should get: "36cf4269-31a6-4f65-a467-f51114715102   Introduction to Theming Basics for Drupal 7   other        custom        drupal"
    And I should get: "6e8b5dbf-5093-4a29-b47b-e052fa2e5a45   Restaurant                                    commerce     product       drupal"
    And I should get: "21e1fada-199c-492b-97bd-0b36b53a9da0   Drupal 7                                      vanilla      core          drupal"
    And I should get: "-------------------------------------- --------------------------------------------- ------------ ------------- -----------"

  @vcr upstream-list.yml
  Scenario: Get info on an upstream
    When I run "terminus upstream:info WordPress"
    Then I should get: "------------- -------------------------------------------------------------------------------"
    And I should get: "ID            e8fe8550-1ab9-4964-8838-2b9abdccf4bf"
    And I should get: "Name          WordPress"
    And I should get: "Category      vanilla"
    And I should get: "Type          core"
    And I should get: "Framework     wordpress"
    And I should get: "URL           https://github.com/pantheon-systems/WordPress"
    And I should get: "Author        Wordpress Community"
    And I should get: "Description   WordPress is web software you can use to create a beautiful website or blog."
    And I should get: "------------- -------------------------------------------------------------------------------"