Feature: Gathering sites' aliases
  As a Pantheon user
  I need to be able to generate a list of aliases
  So that I may make use of Drush effectively.

  Background: I am authenticated
    Given I am authenticated

  @vcr aliases.yml
  Scenario: Generating aliases with printout
    When I run "terminus aliases"
    Then I should get: "/.drush/pantheon.aliases.drushrc.php"

  @vcr aliases.yml
  Scenario: Generating aliases with printout
    When I run "terminus aliases --location=[[cache_dir]]/aliases.php"
    Then I should get: "/aliases.php"

  @vcr aliases.yml
  Scenario: Generating aliases with printout
    When I run "terminus aliases --print"
    Then I should get:
    """
      <?php
        /**
         * Pantheon drush alias file, to be placed in your ~/.drush directory or the aliases
         * directory of your local Drush home. Once it's in place, clear drush cache:
         *
         * drush cc drush
         *
         * To see all your available aliases:
         *
         * drush sa
         *
         * See http://helpdesk.getpantheon.com/customer/portal/articles/411388 for details.
         */

        $aliases['in-search-of-jeffrey-kohler.live'] = array(
          'uri' => 'live-in-search-of-jeffrey-kohler.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:16169/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in',
          'remote-user' => 'live.298f307d-d140-419c-9e7a-5fd25c2fc3d9',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['in-search-of-jeffrey-kohler.dev'] = array(
          'uri' => 'dev-in-search-of-jeffrey-kohler.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:15378/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in',
          'remote-user' => 'dev.298f307d-d140-419c-9e7a-5fd25c2fc3d9',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['in-search-of-jeffrey-kohler.test'] = array(
          'uri' => 'test-in-search-of-jeffrey-kohler.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:13826/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in',
          'remote-user' => 'test.298f307d-d140-419c-9e7a-5fd25c2fc3d9',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.dev'] = array(
          'uri' => 'mysite.com',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.584efe32-191c-4988-b41e-f85753e21684.drush.in:10000/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'dev.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.autopilot'] = array(
          'uri' => 'autopilot-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.autopilot.584efe32-191c-4988-b41e-f85753e21684.drush.in:11796/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.autopilot.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'autopilot.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.perschtest'] = array(
          'uri' => 'perschtest-canary.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.perschtest.584efe32-191c-4988-b41e-f85753e21684.drush.in:10089/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.perschtest.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'perschtest.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.live'] = array(
          'uri' => 'testdomain2.com',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.584efe32-191c-4988-b41e-f85753e21684.drush.in:11824/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'live.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test1'] = array(
          'uri' => 'cost-test1-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test1.584efe32-191c-4988-b41e-f85753e21684.drush.in:14503/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test1.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test1.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test4'] = array(
          'uri' => 'cost-test4-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test4.584efe32-191c-4988-b41e-f85753e21684.drush.in:14031/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test4.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test4.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test7'] = array(
          'uri' => 'cost-test7-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test7.584efe32-191c-4988-b41e-f85753e21684.drush.in:12535/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test7.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test7.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test3'] = array(
          'uri' => 'cost-test3-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test3.584efe32-191c-4988-b41e-f85753e21684.drush.in:13477/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test3.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test3.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test2'] = array(
          'uri' => 'cost-test2-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test2.584efe32-191c-4988-b41e-f85753e21684.drush.in:12887/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test2.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test2.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.cost-test5'] = array(
          'uri' => 'cost-test5-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.cost-test5.584efe32-191c-4988-b41e-f85753e21684.drush.in:14032/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.cost-test5.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'cost-test5.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.test'] = array(
          'uri' => 'pantheon.c',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.584efe32-191c-4988-b41e-f85753e21684.drush.in:10822/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'test.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['canary.tests'] = array(
          'uri' => 'tests-canary.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.tests.584efe32-191c-4988-b41e-f85753e21684.drush.in:13007/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.tests.584efe32-191c-4988-b41e-f85753e21684.drush.in',
          'remote-user' => 'tests.584efe32-191c-4988-b41e-f85753e21684',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.test'] = array(
          'uri' => 'test-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:19650/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'test.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.deadlock'] = array(
          'uri' => 'deadlock-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.deadlock.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:12390/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.deadlock.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'deadlock.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.bugs-604'] = array(
          'uri' => 'bugs-604-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.bugs-604.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:18532/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.bugs-604.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'bugs-604.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.pluginupdat'] = array(
          'uri' => 'pluginupdat-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.pluginupdat.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:14052/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.pluginupdat.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'pluginupdat.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.bugs-609'] = array(
          'uri' => 'bugs-609-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.bugs-609.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:19113/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.bugs-609.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'bugs-609.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.multidev'] = array(
          'uri' => 'multidev-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.multidev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13405/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.multidev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'multidev.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.bugs-609a'] = array(
          'uri' => 'bugs-609a-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.bugs-609a.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:15747/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.bugs-609a.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'bugs-609a.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.dev'] = array(
          'uri' => 'dev-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:10950/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'dev.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.live'] = array(
          'uri' => 'live-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:10218/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'live.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.UPPERCASE'] = array(
          'uri' => 'uppercase-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.UPPERCASE.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:12653/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.UPPERCASE.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'UPPERCASE.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.testing'] = array(
          'uri' => 'testing-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.testing.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13334/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.testing.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'testing.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.blah'] = array(
          'uri' => 'blah-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.blah.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13208/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.blah.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'blah.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['ari.al-333'] = array(
          'uri' => 'al-333-ari.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.al-333.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:14951/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.al-333.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'al-333.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite.live'] = array(
          'uri' => 'live-testsite.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:10093/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite.dev'] = array(
          'uri' => 'dev-testsite.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:11718/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite.test'] = array(
          'uri' => 'test-testsite.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:10748/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite-test.test'] = array(
          'uri' => 'test-testsite-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.68236e6b-b490-43f0-8910-59566f449879.drush.in:14913/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.68236e6b-b490-43f0-8910-59566f449879.drush.in',
          'remote-user' => 'test.68236e6b-b490-43f0-8910-59566f449879',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite-test.live'] = array(
          'uri' => 'live-testsite-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.68236e6b-b490-43f0-8910-59566f449879.drush.in:15732/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.68236e6b-b490-43f0-8910-59566f449879.drush.in',
          'remote-user' => 'live.68236e6b-b490-43f0-8910-59566f449879',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['testsite-test.dev'] = array(
          'uri' => 'dev-testsite-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.68236e6b-b490-43f0-8910-59566f449879.drush.in:14538/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.68236e6b-b490-43f0-8910-59566f449879.drush.in',
          'remote-user' => 'dev.68236e6b-b490-43f0-8910-59566f449879',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['[[test_site_name]].test'] = array(
          'uri' => 'test-[[test_site_name]].pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.11111111-1111-1111-1111-111111111111.drush.in:17434/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.11111111-1111-1111-1111-111111111111.drush.in',
          'remote-user' => 'test.11111111-1111-1111-1111-111111111111',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['[[test_site_name]].live'] = array(
          'uri' => 'live-[[test_site_name]].pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.11111111-1111-1111-1111-111111111111.drush.in:16569/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.11111111-1111-1111-1111-111111111111.drush.in',
          'remote-user' => 'live.11111111-1111-1111-1111-111111111111',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['[[test_site_name]].dev'] = array(
          'uri' => 'dev-[[test_site_name]].pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.11111111-1111-1111-1111-111111111111.drush.in:16698/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.11111111-1111-1111-1111-111111111111.drush.in',
          'remote-user' => 'dev.11111111-1111-1111-1111-111111111111',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['dogwords-buzzwhistle.dev'] = array(
          'uri' => 'dev-dogwords-buzzwhistle.pantheonsite.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.dev.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:15375/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in',
          'remote-user' => 'dev.4abec870-5e12-4bd2-80ca-2d0a55664355',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['dogwords-buzzwhistle.live'] = array(
          'uri' => 'live-dogwords-buzzwhistle.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.live.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:11866/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in',
          'remote-user' => 'live.4abec870-5e12-4bd2-80ca-2d0a55664355',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['dogwords-buzzwhistle.test'] = array(
          'uri' => 'test-dogwords-buzzwhistle.pantheon.io',
          'db-url' => 'mysql://pantheon:12345678901234567890123456789012@dbserver.test.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:14406/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in',
          'remote-user' => 'test.4abec870-5e12-4bd2-80ca-2d0a55664355',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
    """
