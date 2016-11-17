<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\NewRelic;

abstract class NewRelicCommandTest extends CommandTestCase
{

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->site->new_relic = $this->getMockBuilder(NewRelic::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
