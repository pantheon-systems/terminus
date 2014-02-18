<?php
/**
 * @file
 * PHPUnit Tests for pantheon-aliases using Drush's test framework.
 */

class pantheonAliasesTest extends Drush_UnitTestCase {

  public function __construct() {
    parent::__construct();
    // Load Terminus.
    require_once __DIR__ . '/../terminus.drush.inc';
  }

  public function testPantheonAliases() {
    $this->assertFalse(terminus_validate_palises(FALSE));
    $this->assertFalse(terminus_validate_palises('fail'));
    $this->assertFalse(terminus_validate_palises('<?php ?>'));
    $alias = <<<'ALIAS'
<?php
/**
 * This is a Pantheon drush alias file. Place it in your ~/.drush directory or
 * the aliases directory of your local Drush home.
 *
 * To see if it's working, try "drush sa" to list available aliases.
 *
 */
$aliases['launchdemo.test'] = array(
  'root' => '.',
  'uri' => 'SITENAME.gotpantheon.com',
  'db-url' => 'mysql://pantheon:pantheon@dbserver.env.uuid.drush.in:12345/pantheon',
  'db-allows-remote' => TRUE,
  'remote-host' => 'LOL',
  'remote-user' => 'NOPE',
  'ssh-options' => '-p 2222 -o "AddressFamily inet"',
  'path-aliases' => array(
    '%files' => 'code/sites/default/files'
  ),
);
ALIAS;
    $this->assertTrue(terminus_validate_palises($alias));
  }
}