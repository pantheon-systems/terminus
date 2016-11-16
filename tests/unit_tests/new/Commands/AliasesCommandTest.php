<?php
namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\AliasesCommand;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;

class AliasesCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected $aliases;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->aliases = '//Aliases';
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getAliases')
            ->with()
            ->willReturn($this->aliases);

        $this->command = new AliasesCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the aliases command when writing to a the default file
     */
    public function testAliases()
    {
        $default_location = '~/.drush/pantheon.aliases.drushrc.php';
        $location = str_replace('~', $_SERVER['HOME'], $default_location);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Aliases file written to {location}.'),
                $this->equalTo(['location' => $location,])
            );

        $out = $this->command->aliases();
        $this->assertNull($out);
        $this->assertStringEqualsFile($location, $this->aliases);
    }

    /**
     * Tests the aliases command when writing to a named file
     */
    public function testAliasesWithLocation()
    {
        $location_string = '~/.terminus/behatcache/aliases.php';
        $location = str_replace('~', $_SERVER['HOME'], $location_string);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Aliases file written to {location}.'),
                $this->equalTo(['location' => $location,])
            );

        $out = $this->command->aliases(['location' => $location_string,]);
        $this->assertNull($out);
        $this->assertStringEqualsFile($location, $this->aliases);
    }

    /**
     * Tests the aliases command when it is outputting to the screen
     */
    public function testAliasesPrint()
    {
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->aliases(['print' => true,]);
        $this->assertEquals($out, $this->aliases);
    }
}
