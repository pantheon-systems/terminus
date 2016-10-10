<?php

namespace Pantheon\Terminus\UnitTests;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Terminus;

/**
 * Testing class for Pantheon\Terminus\Terminus
 */
class TerminusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $name = 'Terminus';

    /**
     * Tests the Terminus constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $config = new Config();
        $terminus = new Terminus($this->name, $config->get('version'), $config);
        $this->assertAttributeInstanceOf('Pantheon\Terminus\Config', 'config', $terminus);
        $this->assertEquals($terminus->getName(), $this->name);
        $this->assertEquals($terminus->getVersion(), $config->get('version'));
        $this->assertArrayHasKey('yes', $terminus->getDefinition()->getOptions());
        $this->assertInstanceOf(
            'Symfony\Component\Console\Input\InputOption',
            $terminus->getDefinition()->getOption('yes')
        );
    }
}
