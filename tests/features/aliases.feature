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

        $aliases['thenation.deekshant'] = array(
          'uri' => 'deekshant-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:e744f99f39804290bf716918826e7c2c@dbserver.deekshant.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:10032/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.deekshant.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'deekshant.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.bsd'] = array(
          'uri' => 'bsd-thenation.pantheon.io',
          'db-url' => 'mysql://pantheon:335f66c04f784192a9044747416f1138@dbserver.bsd.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12609/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.bsd.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'bsd.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.harshad'] = array(
          'uri' => 'harshad-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:bc36277fc6a948aa9a78f1108a4d7578@dbserver.harshad.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:17370/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.harshad.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'harshad.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.akshay'] = array(
          'uri' => 'akshay-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:8a3b2cbe3a914e2aacc8ab8008c2142b@dbserver.akshay.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:16292/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.akshay.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'akshay.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.dev'] = array(
          'uri' => 'horizon1.thenation.com',
          'db-url' => 'mysql://pantheon:8e8f8c4f72714810b91272b39bf63240@dbserver.dev.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12145/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'dev.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.devloper'] = array(
          'uri' => 'devloper-thenation.pantheon.io',
          'db-url' => 'mysql://pantheon:a1d0271e7bc44b299f18f47ab4661915@dbserver.devloper.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12323/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.devloper.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'devloper.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.erik-solr'] = array(
          'uri' => 'erik-solr-thenation.pantheon.io',
          'db-url' => 'mysql://pantheon:edcd68150788424cad5343921e7480e9@dbserver.erik-solr.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:10312/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.erik-solr.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'erik-solr.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.test'] = array(
          'uri' => 'test-thenation.pantheon.io',
          'db-url' => 'mysql://pantheon:78d1e75b2b3e43c5aa32acc7d501d002@dbserver.test.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:10321/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'test.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.adtest'] = array(
          'uri' => 'adtest.thenation.com',
          'db-url' => 'mysql://pantheon:1d4e07fc23654b7796106a4c6de481b3@dbserver.adtest.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12357/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.adtest.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'adtest.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.akamai'] = array(
          'uri' => 'akamai-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:e00adbe77e2a4c5bb524c89261f60520@dbserver.akamai.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12823/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.akamai.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'akamai.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.drupal'] = array(
          'uri' => 'drupal-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:21aa21c2c4124760b97dbd5becf5d8cd@dbserver.drupal.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:12146/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.drupal.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'drupal.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.ritesh'] = array(
          'uri' => 'ritesh-thenation.pantheon.io',
          'db-url' => 'mysql://pantheon:ad0e83611f544ebebdd7686173a13199@dbserver.ritesh.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:16424/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.ritesh.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'ritesh.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['thenation.live'] = array(
          'uri' => 'live-thenation.pantheonsite.io',
          'db-url' => 'mysql://pantheon:b35cb8c7d81b4ff0a54a7af315773f96@dbserver.live.672ae238-dede-44f4-9243-89a591c2cc46.drush.in:10000/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.672ae238-dede-44f4-9243-89a591c2cc46.drush.in',
          'remote-user' => 'live.672ae238-dede-44f4-9243-89a591c2cc46',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['in-search-of-jeffrey-kohler.live'] = array(
          'uri' => 'live-in-search-of-jeffrey-kohler.pantheonsite.io',
          'db-url' => 'mysql://pantheon:5e71badff8a04ab997b719265355a221@dbserver.live.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:16169/pantheon',
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
          'db-url' => 'mysql://pantheon:df783b09617d48798cf93fa8d07ab34e@dbserver.dev.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:15378/pantheon',
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
          'db-url' => 'mysql://pantheon:a6cd3b798c39493ab8499af11829c86e@dbserver.test.298f307d-d140-419c-9e7a-5fd25c2fc3d9.drush.in:13826/pantheon',
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
          'db-url' => 'mysql://pantheon:742f2c7703bb4d9eb3197a0cc721b85f@dbserver.dev.584efe32-191c-4988-b41e-f85753e21684.drush.in:10000/pantheon',
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
          'db-url' => 'mysql://pantheon:00ee8be83ca7465a8f43a48ed3e304f1@dbserver.autopilot.584efe32-191c-4988-b41e-f85753e21684.drush.in:11796/pantheon',
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
          'db-url' => 'mysql://pantheon:faa545ee702b47cfa781308e2f019e0f@dbserver.perschtest.584efe32-191c-4988-b41e-f85753e21684.drush.in:10089/pantheon',
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
          'db-url' => 'mysql://pantheon:63c4a6b7602241bf97176fdc652a8e43@dbserver.live.584efe32-191c-4988-b41e-f85753e21684.drush.in:11824/pantheon',
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
          'db-url' => 'mysql://pantheon:a8c8a6f6dfe04a20905e2e1fd5752aa6@dbserver.cost-test1.584efe32-191c-4988-b41e-f85753e21684.drush.in:14503/pantheon',
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
          'db-url' => 'mysql://pantheon:da65e213ca9943ab82eb225602ee103a@dbserver.cost-test4.584efe32-191c-4988-b41e-f85753e21684.drush.in:14031/pantheon',
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
          'db-url' => 'mysql://pantheon:b8b88f92a5ca47248dbc48122e8599f0@dbserver.cost-test7.584efe32-191c-4988-b41e-f85753e21684.drush.in:12535/pantheon',
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
          'db-url' => 'mysql://pantheon:0c1dc8b25fdb4276ba5203271bf33377@dbserver.cost-test3.584efe32-191c-4988-b41e-f85753e21684.drush.in:13477/pantheon',
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
          'db-url' => 'mysql://pantheon:0e98e77e1d8b42df828133d91be2d182@dbserver.cost-test2.584efe32-191c-4988-b41e-f85753e21684.drush.in:12887/pantheon',
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
          'db-url' => 'mysql://pantheon:b5b90095200d4f679eab37197e5e8a6c@dbserver.cost-test5.584efe32-191c-4988-b41e-f85753e21684.drush.in:14032/pantheon',
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
          'db-url' => 'mysql://pantheon:20570b52336843f3ab9519ddfb6e13f1@dbserver.test.584efe32-191c-4988-b41e-f85753e21684.drush.in:10822/pantheon',
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
          'db-url' => 'mysql://pantheon:f2b16a9f2b6a483d887b24693b893b45@dbserver.tests.584efe32-191c-4988-b41e-f85753e21684.drush.in:13007/pantheon',
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
          'db-url' => 'mysql://pantheon:070b9ead32304f18a8d2a329651f2c57@dbserver.test.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:19650/pantheon',
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
          'db-url' => 'mysql://pantheon:7f1f6d7cb5584cac989557c1d50694c0@dbserver.deadlock.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:12390/pantheon',
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
          'db-url' => 'mysql://pantheon:7cab13f0ad044725a6f40339bdbbb684@dbserver.bugs-604.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:18532/pantheon',
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
          'db-url' => 'mysql://pantheon:7ce91d77b5704aa08b9b10ad748cc336@dbserver.pluginupdat.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:14052/pantheon',
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
          'db-url' => 'mysql://pantheon:a28d183e6b5b4a3b98529e81d7dce00d@dbserver.bugs-609.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:19113/pantheon',
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
          'db-url' => 'mysql://pantheon:04ade781fc924e2aaea2fe91e0ddecdc@dbserver.multidev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13405/pantheon',
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
          'db-url' => 'mysql://pantheon:2e8fec1ffffe42c1ab89d5dea5370229@dbserver.bugs-609a.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:15747/pantheon',
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
          'db-url' => 'mysql://pantheon:e2b603c9adfa4e129dbb8f9cbfc1c1f9@dbserver.dev.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:10950/pantheon',
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
          'db-url' => 'mysql://pantheon:4cbb7aec55f14f979dfcc439b5c56da6@dbserver.live.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:10218/pantheon',
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
          'db-url' => 'mysql://pantheon:a10c0ced82bd46289e47e7ea4fa5a9f5@dbserver.UPPERCASE.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:12653/pantheon',
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
          'db-url' => 'mysql://pantheon:924646f4362744d6a58d944572cc1450@dbserver.testing.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13334/pantheon',
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
          'db-url' => 'mysql://pantheon:b9ae4449da4e417680d215f60cb911a3@dbserver.blah.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:13208/pantheon',
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
          'db-url' => 'mysql://pantheon:f8a96fcf3070486396d7f8ca694de8e5@dbserver.al-333.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in:14951/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.al-333.932bdc35-0b38-4222-b87b-eccf498eedde.drush.in',
          'remote-user' => 'al-333.932bdc35-0b38-4222-b87b-eccf498eedde',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray.live'] = array(
          'uri' => 'live-tesladethray.pantheon.io',
          'db-url' => 'mysql://pantheon:b3eb7143c51443429ce9dcd5925241aa@dbserver.live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:10093/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'live.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray.dev'] = array(
          'uri' => 'dev-tesladethray.pantheon.io',
          'db-url' => 'mysql://pantheon:ccf855a337884e349d1f08e42d61e0fa@dbserver.dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:11718/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'dev.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray.test'] = array(
          'uri' => 'test-tesladethray.pantheon.io',
          'db-url' => 'mysql://pantheon:f85cf5bc81e84bb389507e272808a8a1@dbserver.test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in:10748/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e.drush.in',
          'remote-user' => 'test.a6f89eab-0c11-4eee-89a3-9a8cb609cc6e',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray-test.test'] = array(
          'uri' => 'test-tesladethray-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:e825d3d597b54ad18c1f03f4026cb331@dbserver.test.68236e6b-b490-43f0-8910-59566f449879.drush.in:14913/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.test.68236e6b-b490-43f0-8910-59566f449879.drush.in',
          'remote-user' => 'test.68236e6b-b490-43f0-8910-59566f449879',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray-test.live'] = array(
          'uri' => 'live-tesladethray-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:1724c8090145438799468d1d2f6a8b21@dbserver.live.68236e6b-b490-43f0-8910-59566f449879.drush.in:15732/pantheon',
          'db-allows-remote' => TRUE,
          'remote-host' => 'appserver.live.68236e6b-b490-43f0-8910-59566f449879.drush.in',
          'remote-user' => 'live.68236e6b-b490-43f0-8910-59566f449879',
          'ssh-options' => '-p 2222 -o "AddressFamily inet"',
          'path-aliases' => array(
            '%files' => 'code/sites/default/files',
            '%drush-script' => 'drush',
           ),
        );
        $aliases['tesladethray-test.dev'] = array(
          'uri' => 'dev-tesladethray-test.pantheonsite.io',
          'db-url' => 'mysql://pantheon:e79bc6fa0556457889f59000b42a91aa@dbserver.dev.68236e6b-b490-43f0-8910-59566f449879.drush.in:14538/pantheon',
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
          'db-url' => 'mysql://pantheon:ac7b3205bb5b4f6884c4cddf99dfa4c3@dbserver.test.11111111-1111-1111-1111-111111111111.drush.in:17434/pantheon',
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
          'db-url' => 'mysql://pantheon:a7dd95d3d52f40f3806b7a307e1fe748@dbserver.live.11111111-1111-1111-1111-111111111111.drush.in:16569/pantheon',
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
          'db-url' => 'mysql://pantheon:ad7e59695d264b3782c2a9fd959d6a40@dbserver.dev.11111111-1111-1111-1111-111111111111.drush.in:16698/pantheon',
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
          'db-url' => 'mysql://pantheon:cad86bf4be8442fe98ce03b9b8711ddf@dbserver.dev.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:15375/pantheon',
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
          'db-url' => 'mysql://pantheon:228d283a8f6c4fbcb36bcbf47ab11e2f@dbserver.live.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:11866/pantheon',
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
          'db-url' => 'mysql://pantheon:667763e00316418ca373cfb9cd6bc915@dbserver.test.4abec870-5e12-4bd2-80ca-2d0a55664355.drush.in:14406/pantheon',
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
