<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\DiffStatCommand;

/**
 * Class DiffStatCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\DiffStatCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class DiffStatCommandTest extends EnvCommandTest
{
    public function setUp()
    {
        parent::setUp();

        $this->command = new DiffStatCommand();
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    public function testDiffStat()
    {
        $diffs = [
            'myfile.txt' => ['status' => 'm', 'deletions' => 2, 'additions' => 4],
            'another.txt' => ['status' => 'm', 'deletions' => 3, 'additions' => 0]
        ];
        $expected = [
            ['file' => 'myfile.txt'] + $diffs['myfile.txt'],
            ['file' => 'another.txt'] + $diffs['another.txt'],
        ];
        $this->environment->expects($this->once())
            ->method('diffstat')
            ->willReturn($diffs);
        $out = $this->command->diffstat('mysite.dev');

        $this->assertEquals($expected, $out->getArrayCopy());
    }

    public function testDiffStatEmpty()
    {
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('No changes on server.')
            );

        $this->environment->expects($this->once())
            ->method('diffstat')
            ->willReturn([]);
        $out = $this->command->diffstat('mysite.dev');
        $this->assertEquals([], $out->getArrayCopy());
    }
}
