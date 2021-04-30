<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class WorkflowOperationTest
 * Testing class for Pantheon\Terminus\Models\WorkflowOperation
 * @package Pantheon\Terminus\UnitTests\Models
 */
class WorkflowOperationTest extends ModelTestCase
{
    /**
     * Tests the WorkflowOperation::description() function
     */
    public function testDescription()
    {
        $wfop = new WorkflowOperation((object)['description' => 'Dumbo Drop', 'run_time' => 1.2345,]);
        $this->assertEquals('Operation: Dumbo Drop finished in 1s', $wfop->description());

        $wfop2 = new WorkflowOperation((object)['description' => 'Dumbo Drop',]);
        $this->assertEquals('Operation: Dumbo Drop finished in ', $wfop2->description());
    }

    /**
     * Tests the WorkflowOperation::serialize() function
     */
    public function testSerialize()
    {
        $data = [
            'id' => '123',
            'description' => 'Dumbo Drop',
            'run_time' => 1.2345,
            'type' => 'platform',
            'result' => 'success',
            'log_output' => 'The operation was a total success',
            'other' => 'something else',
        ];
        $wfop = new WorkflowOperation((object)$data);

        $data['duration'] = '1s';
        unset($data['other']);
        unset($data['run_time']);
        $this->assertEquals($data, $wfop->serialize());
    }

    /**
     * Tests the WorflowOperation::__toString() function
     */
    public function testToString()
    {
        $data = [
            'description' => 'Dumbo Drop',
            'run_time' => 1.2345,
            'log_output' => 'The operation was a total success',
            'environment' => 'multidev',
        ];
        $wfop = new WorkflowOperation((object)$data);

        $this->assertEquals(
            "------ Operation: Dumbo Drop finished in 1s (multidev) ------\nThe operation was a total success",
            (string)$wfop
        );
    }
}
