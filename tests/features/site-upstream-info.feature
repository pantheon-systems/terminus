Feature: Get information about a site's upstream
  In order to know where my site's source is
  As a user
  I need to be able to view information about its upstream.

  Background: I am authenticated and I have a site named [[test_site_name]]
    Given I am authenticated
    And a site named "[[test_site_name]]"

  @vcr site-upstream-info.yml
  Scenario: Retrieving information about a site's upstream
    When I run "terminus site:upstream:info [[test_site_name]]"
    Then I should get:
    """
    ------------- ----------------------------------------------------------------------------------------------------------------------------------------------
    ID            974b75c2-4ba7-49f8-8a54-3a45c07dfe02
    Name          Drupal 6
    Category      vanilla
    Type          core
    Framework     drupal
    URL           git://github.com/pantheon-systems/drops-6.git
    Author
    Description   Drupal 6 sites run on Pantheon, but  <a href="https://www.drupal.org/drupal-6-eol">Drupal 6 reached end of life</a> on February 24th, 2016.

    ------------- ----------------------------------------------------------------------------------------------------------------------------------------------
    """
