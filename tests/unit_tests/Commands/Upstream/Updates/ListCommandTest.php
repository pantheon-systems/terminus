<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\Upstream\Updates\ListCommand;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\Updates\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream\Updates
 */
class ListCommandTest extends UpdatesCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the upstream:updates:list command when there are no updates to apply
     */
    public function testListUpstreamsEmpty()
    {
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

        $out = $this->command->listUpstreamUpdates('123');
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the upstream:updates:list command
     */
    public function testListUpstreams()
    {
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

        $out = $this->command->listUpstreamUpdates('123');
        $result = [
            [
                'hash' => '1bc423f65b3cc527b77d91da5c95eb240d9484f0',
                'datetime' => '2016-06-16T04:21:14',
                'message' => 'Update to Drupal 7.44. For more information, see ' .
                    'https://www.drupal.org/project/drupal/releases/7.44.',
                'author' => 'Pantheon Automation',
            ],
            [
                'hash' => '2f1c945d01cd03250e2b6668ad77bf24f54a5a56',
                'datetime' => '2016-07-07T20:24:52',
                'message' => 'Update to Drupal 7.50. For more information, see ' .
                    'https://www.drupal.org/project/drupal/releases/7.50',
                'author' => 'Pantheon Automation',
            ],
        ];
        $this->assertEquals($result, $out->getArrayCopy());
    }

    /**
     * Tests the upstream:updates:list command when getUpdates returns empty (i.e. erred)
     */
    public function testListUpstreamsErred()
    {
        $this->upstream->method('getUpdates')
            ->willReturn([]);
        $this->logger->expects($this->never())
            ->method('log');
        $this->setExpectedException(TerminusException::class, 'There was a problem checking your upstream status. Please try again.');

        $out = $this->command->listUpstreamUpdates('123');
        $this->assertNull($out);
    }
}
