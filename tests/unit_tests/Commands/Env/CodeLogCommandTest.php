<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CodeLogCommand;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Commit;

/**
 * Testing class for Pantheon\Terminus\Commands\Env\CodeLogCommand
 */
class CodeLogCommandTest extends EnvCommandTest
{
    /**
     * Sets up the test fixtures.
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
     * Tests the env:log command success with all parameters.
     *
     * @return void
     */
    public function testLog()
    {
        $this->environment->id = 'dev';
        $this->commits->method('all')
            ->willReturn([
                $this->commit_1,
                $this->commit_2,
            ]);

        $out = $this->command->codeLog('mysite.dev');

        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals(count($out), 2);
        $out_1 = $out->getArrayCopy()[0];
        $this->assertEquals($this->commit_1_attribs['datetime'], $out_1['time']);
        $this->assertEquals($this->commit_1_attribs['author'], $out_1['author']);
        $this->assertEquals($this->commit_1_attribs['hash'], $out_1['hash']);
        $this->assertEquals($this->commit_1_attribs['message'], $out_1['message']);
        $this->assertEquals('test, dev', $out_1['labels']);
    }

    /**
     * Tests the env:deploy command where no log is available.
     *
     * @return void
     */
    public function testDeployNoCode()
    {
        $this->environment->id = 'dev';
        $this->commits->method('all')
            ->willReturn([]);

        $out = $this->command->codeLog('mysite.dev');

        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals(count($out), 0);
    }
}
