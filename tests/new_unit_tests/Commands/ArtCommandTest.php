<?php
namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Tests\CommandTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setInput(['command' => 'art', 'name' => 'hello']);
    }

    public function testArtPrintsArt()
    {
        $this->assertEquals('Hello World!', $this->runCommand()->getOutput());
    }

    public function testArtRejectsNonExistantFiles()
    {
        $this->setInput(['command' => 'art', 'name' => 'foo']);
        $this->assertEquals('Not a valid work of art!', $this->runCommand()->getOutput());
    }
}
