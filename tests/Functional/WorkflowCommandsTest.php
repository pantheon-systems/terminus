<?php

namespace Pantheon\Terminus\Tests\Functional;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitException;

/**
 * Class WorkflowCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class WorkflowCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\ListCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\StatusCommand
     *
     * @group workflow
     * @group short
     */
    public function testWorkflowListAndStatusCommand()
    {
        $workflow = $this->getLatestWorkflow();
        $workflowStatus = $this->terminusJsonResponse(
            sprintf('workflow:info:status %s --id=%s', $this->getSiteName(), $workflow['id'])
        );

        unset($workflowStatus['time']);
        unset($workflow['time']);
        $this->assertEquals($workflowStatus, $workflow);
    }


    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\ListCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\LogsCommand
     *
     * @group workflow
     * @group short
     *
     * Requirements:
     * 1. Test site has print-test-message.php script file in web/private/scripts/quicksilver/ path on dev environment
     *    with the following lines:
     *    <code>
     *    <?php
     *    print 'This message should be printed after env:clear-cache Terminus command execution.';
     *    </code>
     *  2. Test site has the following quicksilver config in pantheon.yml file on dev environment:
     *    <code>
     *    workflows:
     *      after:
     *       - type: webphp
     *         description: Print test message
     *         script: private/scripts/quicksilver/print-test-message.php
     *    </code>
     */
    public function testWorkflowOperationsAndLogsCommands()
    {
        $this->terminus(sprintf('env:clear-cache %s', $this->getSiteEnv()));

        $workflow = $this->getLatestWorkflow(['workflow' => sprintf('Clear cache for "%s"', $this->getMdEnv())]);
        $this->assertEquals('succeeded', $workflow['status']);
        $this->assertEquals($this->getMdEnv(), $workflow['env']);

        $operations = $this->terminusJsonResponse(
            sprintf('workflow:info:operations %s --id=%s', $this->getSiteName(), $workflow['id'])
        );
        $this->assertIsArray($operations);
        $this->assertNotEmpty($operations);

        // Find the first non-'platform' operation
        $testOperation = array_pop($operations);
        while (!empty($testOperation) && ('platform' == $testOperation['type'] ?: '')) {
            $testOperation = array_pop($operations);
        }

        // In order for this test to pass, there must be an entry in
        // the test fixture site's pantheon.yml file:
        //
        // api_version: 1
        // workflows:
        //   clear_cache:
        //     after:
        //       - type: webphp
        //         description: Print test message
        //         script: private/scripts/quicksilver/print-test-message.php
        //
        // The contents of the script do not matter, as long as it completes successfully.

        $this->assertIsArray($testOperation);
        $this->assertNotEmpty($testOperation);
        $this->assertArrayHasKey('type', $testOperation);
        $this->assertEquals('quicksilver', $testOperation['type']);
        $this->assertArrayHasKey('result', $testOperation);
        $this->assertEquals('succeeded', $testOperation['result']);
        $this->assertArrayHasKey('duration', $testOperation);
        $this->assertArrayHasKey('description', $testOperation);

        $logs = $this->terminus(
            sprintf('workflow:info:logs %s --id=%s', $this->getSiteName(), $workflow['id'])
        );
        $this->assertIsString($logs);
        $this->assertNotEmpty($logs);

        $this->assertTrue(
            false !== strpos($logs, 'This message should be printed after env:clear-cache Terminus command execution.'),
            'Workflow log should contain the test message'
        );
    }

    /**
     * testWorkflowWaitForCommitCommand
     *
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\WaitForCommitCommand
     * @group workflow
     * @group short
     * @throws GitException
     */
    public function testWorkflowWaitForCommitCommand()
    {
        $command = "vendor/bin/robo generate:test-commit";
        // this should have returned the commit has from that test commit
        $response = exec($command);
        $this->assertStringContainsString('Commit hash:', $response);
        $commitHash = explode(':', $response)[1];
        $err = $this->terminus(sprintf('workflow:wait-for-commit %s --commit=%s', $this->getSiteName(), $commitHash));
        $this->assertEmpty($err, 'Terminus command should not return any error: %s', $err);
    }

    /**
     * Tests and returns the latest workflow record.
     *
     * @param array $filters
     *   The list of filters (field name => expected value).
     *
     * @return array
     *   The workflow metadata.
     */
    private function getLatestWorkflow(array $filters = []): array
    {
        $workflowsList = $this->terminusJsonResponse(sprintf('workflow:list %s', $this->getSiteName()));
        $this->assertIsArray($workflowsList);
        $this->assertNotEmpty($workflowsList);

        if ($filters) {
            $workflowsList = array_filter(
                $workflowsList,
                fn($workflow): bool => count(array_intersect_assoc($filters, $workflow)) === count($filters)
            );
        }

        $workflow = array_shift($workflowsList);

        $fields = [
            'id',
            'env',
            'workflow',
            'user',
            'status',
            'started_at',
            'finished_at',
            'time',
        ];
        foreach ($fields as $field) {
            $this->assertArrayHasKey(
                $field,
                $workflow,
                sprintf('Workflow should have "%s" field', $field)
            );
        }

        return $workflow;
    }
}
