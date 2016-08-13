Feature: Instruments command
  In order to pay for service
  As a user
  I need to be able to view and use my payment instruments.

  Background: I am logged in
    Given I am authenticated

  @vcr instruments_list
  Scenario: Listing a user's payment instruments
    When I run "terminus instruments list"
    Then I should get: "[[instrument_label]]"
