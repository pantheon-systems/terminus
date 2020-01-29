<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\Upstream\Updates\ApplyCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class ApplyCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\Updates\ApplyCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream\Updates
 */
class ApplyCommandTest extends UpdatesCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ApplyCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the upstream:updates:apply command when there are no updates to apply
     */
    public function testApplyUpdatesNone()
    {
        $this->environment->id = 'dev';

        $upstream_data = (object)[
            'remote_head' => '2f1c945d01cd03250e2b6668ad77bf24f54a5a56',
            'ahead' => 1,
            'update_log' => (object)[],
        ];

        $this->upstream_status->expects($this->once())
            ->method('getUpdates')
            ->with()
            ->willReturn($upstream_data);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo('There are no available updates for this site.')
            );

        $this->environment->expects($this->never())
            ->method('applyUpstreamUpdates');

        $out = $this->command->applyUpstreamUpdates('123', ['accept-updates' => true, 'updatedb' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests the upstream:updates:apply command
     */
    public function testApplyUpdates()
    {
        $this->environment->id = 'dev';

        $upstream_data = (object)[
            'remote_head' => '2f1c945d01cd03250e2b6668ad77bf24f54a5a56',
            'ahead' => 1,
            'update_log' => (object)[
                '1bc423f65b3cc527b77d91da5c95eb240d9484f0' => (object)[
                    'gravitar_url' => 'http://pantheon-content.s3.amazonaws.com/blank_user.png',
                    'hash' => '1bc423f65b3cc527b77d91da5c95eb240d9484f0',
                    'author' => 'Pantheon Automation',
                    'labels' => [],
                    'datetime' => '2016-06-16T04:21:14',
                    'parents' => [
                        '45be60a4e82bc42b34bde2b6f02f4d2885a05eed'
                    ],
                    'message' => 'Update to Drupal 7.44. For more information, see ' .
                        'https://www.drupal.org/project/drupal/releases/7.44.',
                    'email' => 'bot@getpantheon.com',
                ],
                '2f1c945d01cd03250e2b6668ad77bf24f54a5a56' => (object)[
                    'gravitar_url' => 'http://pantheon-content.s3.amazonaws.com/blank_user.png',
                    'hash' => '2f1c945d01cd03250e2b6668ad77bf24f54a5a56',
                    'author' => 'Pantheon Automation',
                    'labels' => [],
                    'datetime' => '2016-07-07T20:24:52',
                    'parents' => [
                        '45be60a4e82bc42b34bde2b6f02f4d2885a05eed'
                    ],
                    'message' => 'Update to Drupal 7.50. For more information, see ' .
                        'https://www.drupal.org/project/drupal/releases/7.50',
                    'email' => 'bot@getpantheon.com',
                ],
            ],
        ];
        $this->upstream_status->method('getUpdates')
            ->willReturn($upstream_data);

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->any())
            ->method('getMessage')
            ->willReturn('Applied upstream updates to "dev"');

        $this->environment->expects($this->once())
            ->method('applyUpstreamUpdates')
            ->with($this->equalTo(true), $this->equalTo(true))
            ->willReturn($workflow);
        $this->site->expects($this->once())
            ->method('get')
            ->with('name')
            ->willReturn('my-site');

        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Applying {count} upstream update(s) to the {env} environment of {site_id}...'),
                $this->equalTo(['count' => 2, 'env' => 'dev', 'site_id' => 'my-site'])
            );

        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Applied upstream updates to "dev"')
            );

        $out = $this->command->applyUpstreamUpdates('my-site', ['accept-upstream' => true, 'updatedb' => true,]);
        $this->assertNull($out);
    }

    /**
     * Tests the upstream:updates:apply command when there are no updates to apply
     */
    public function testApplyUpdatesTestOrLive()
    {
        $this->environment->id = 'live';

        $this->upstream->expects($this->never())
            ->method('getUpdates');
        $this->logger->expects($this->never())
            ->method('log');
        $this->environment->expects($this->never())
            ->method('applyUpstreamUpdates');

        $this->setExpectedException(
            TerminusException::class,
            "Upstream updates cannot be applied to the {$this->environment->id} environment"
        );

        $out = $this->command->applyUpstreamUpdates('123', ['accept-updates' => true, 'updatedb' => true,]);
        $this->assertNull($out);
    }
}
