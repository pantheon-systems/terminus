<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\NewRelic;

/**
 * Class NewRelicCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\NewRelic
 */
abstract class NewRelicCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->new_relic = $this->getMockBuilder(NewRelic::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->method('getNewRelic')->willReturn($this->new_relic);
    }
}
