<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CodeLogCommand;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Commit;

/**
 * Class CodeLogCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\CodeLogCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class CodeLogCommandTest extends EnvCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CodeLogCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);

        $this->commits = $this->getMockBuilder(Commits::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->method('getCommits')->willReturn($this->commits);

        $this->commit_1_attribs = [
            'datetime' => '2016-09-21T12:21:18',
            'author' => 'Daisy Duck',
            'labels' => ['test', 'dev'],
            'hash' => 'c65e638f03cabc7b97e686bb9de843b7173e329a',
            'message' => 'Add some new code',
        ];
        $this->commit_1 = new Commit((object)$this->commit_1_attribs);
        $this->commit_2_attribs = [
            'datetime' => '2016-09-16T06:53:48',
            'author' => 'Donald Duck',
            'labels' => ['test', 'dev'],
            'hash' => 'bccb7d4972a458e6c788c46bd1afb2de47d88ee3',
            'message' => 'Remove some old code',
        ];
        $this->commit_2 = new Commit((object)$this->commit_2_attribs);
    }

    /**
     * Tests the env:log command success with all parameters
     */
    public function testLog()
    {
        $data = ['1' => [
            'datetime' => '2016-09-21T12:21:18',
            'author' => 'Daisy Duck',
            'labels' => ['test', 'dev'],
            'hash' => 'c65e638f03cabc7b97e686bb9de843b7173e329a',
            'message' => 'Add some new code',
        ]];
        $this->environment->id = 'dev';
        $this->commits->method('serialize')
            ->willReturn($data);

        $out = $this->command->codeLog('mysite.dev');

        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }

    /**
     * Tests the env:deploy command where no log is available
     */
    public function testDeployNoCode()
    {
        $this->environment->id = 'dev';
        $this->commits->method('serialize')
            ->willReturn([]);

        $out = $this->command->codeLog('mysite.dev');

        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals(count($out), 0);
    }
}
