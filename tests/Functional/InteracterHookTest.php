<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Hooks\Interacter;
use Symfony\Component\Console\Input\ArgvInput;
use Pantheon\Terminus\Config\TerminusConfig;

/**
 * Class InteracterHookTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class InteracterHookTest extends TerminusTestBase
{
    /**
     * @var Pantheon\Terminus\Hooks\Interacter
     */
    protected $interacter;

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->interacter = new Interacter();
        $config = new TerminusConfig();
        $this->interacter->setConfig($config);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Hooks\Interacter
     *
     * @group interacter
     * @group short
     */
    public function testInferTypeFromName()
    {
        $name = 'password';
        $type = $this->interacter->inferTypeFromName($name);
        $this->assertEquals('password', $type, 'The type should be "password".');

        $name = 'username';
        $type = $this->interacter->inferTypeFromName($name);
        $this->assertEquals('string', $type, 'The type should be "string".');

        $name = 'email';
        $type = $this->interacter->inferTypeFromName($name);
        $this->assertEquals('string', $type, 'The type should be "string".');

        $name = "upstream_id";
        $type = $this->interacter->inferTypeFromName($name);
        $this->assertEquals('upstream', $type, 'The type should be "upstream".');

        $name = "org";
        $type = $this->interacter->inferTypeFromName($name);
        $this->assertEquals('organization', $type, 'The type should be "organization".');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Hooks\Interacter
     *
     * @group interacter
     * @group short
     */
    public function testGetRegionList()
    {
        $regions = $this->interacter->getRegionList();
        $this->assertIsArray($regions, 'The regions should be an array.');
        $this->assertNotEmpty($regions, 'The regions array should not be empty.');

        $empty_found = false;
        foreach ($regions as $key => $region) {
            if (empty($key)) {
                $empty_found = true;
                break;
            }
        }
        $this->assertFalse($empty_found, 'The regions array should NOT contain an empty key.');

        $regions = $this->interacter->getRegionList(true);
        $this->assertIsArray($regions, 'The regions should be an array.');
        $this->assertNotEmpty($regions, 'The regions array should not be empty.');
        $empty_found = false;
        foreach ($regions as $key => $region) {
            if (empty($key)) {
                $empty_found = true;
                break;
            }
        }
        $this->assertTrue($empty_found, 'The regions array should contain an empty key.');
    }
}
