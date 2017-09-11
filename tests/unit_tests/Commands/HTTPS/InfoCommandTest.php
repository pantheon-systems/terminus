<?php

namespace Pantheon\Terminus\UnitTests\HTTPS;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Commands\HTTPS\InfoCommand;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\HTTPS\InfoCommand
 * @package Pantheon\Terminus\UnitTests\HTTPS
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var Domain
     */
    protected $domain;
    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->domains = $this->getMockBuilder(Domains::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = ['some' => 'data', 'for' => 'testing',];

        $this->environment->expects($this->once())
            ->method('getDomains')
            ->with()
            ->willReturn($this->domains);
        $this->domains->expects($this->once())
            ->method('fetchWithRecommendations')
            ->with()
            ->willReturn($this->domains);
        $this->domains->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);

        $this->command = new InfoCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the https:info command when HTTPS is active on the given environment
     */
    public function testInfo()
    {
        $out = $this->command->info('site.env');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($out->getArrayCopy(), $this->data);
    }
}
