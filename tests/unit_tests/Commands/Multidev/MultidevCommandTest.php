<?php

namespace Pantheon\Terminus\UnitTests\Commands\Multidev;

use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class MultidevCommandTest
 * Testing base class for Pantheon\Terminus\Commands\Multidev\*
 * @package Pantheon\Terminus\UnitTests\Commands\Multidev
 */
abstract class MultidevCommandTest extends CommandTestCase
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
