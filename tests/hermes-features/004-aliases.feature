Feature: Gathering sites' aliases
  As a Pantheon user
  I need to be able to generate a list of aliases
  So that I may make use of Drush effectively.

  Background: I am authenticated
    Given I am authenticated

  Scenario: Generating aliases with printout
    When I run "terminus aliases"
    Then I should get: "/.drush/pantheon.aliases.drushrc.php"

  Scenario: Generating aliases with printout
    When I run "terminus aliases --location=[[cache_dir]]/aliases.php"
    Then I should get: "/aliases.php"

  Scenario: Generating aliases with printout
    Given a site named "[[test_site_name]]" already exists
    When I run "terminus aliases --print"
    Then I should get the notice: "Fetching site information to build Drush aliases..."
    And I should get: "sites found."
    And I should get the notice: "Displaying Drush 8 alias file contents."
    And I should get: "$aliases['[[test_site_name]].*'] = array("
    And I should get: "'uri' => '${env-name}-[[test_site_name]].[[php_site_domain]]',"
    And I should get: "'remote-host' => 'appserver.${env-name}."
    And I should get: "'remote-user' => '${env-name}."
    And I should get:
    """
       'ssh-options' => '-p 2222 -o "AddressFamily inet"',
       'path-aliases' => array(
         '%files' => 'files',
        ),
     );
    """
