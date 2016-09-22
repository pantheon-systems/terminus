<?php
namespace Pantheon\Terminus\UnitTests\Commands;

class ArtCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setInput(['command' => 'art', 'name' => 'hello']);
    }

    /**
     * @test
     */
    public function artCommandPrintsContentsOfFilesInAssetsDirectory()
    {
        $this->assertEquals('Hello World!', $this->runCommand()->fetchTrimmedOutput());
    }

    /**
     * @test
     */
    public function artCommandRejectsFilesNotInAssetsDirectory()
    {
        $this->setInput(['command' => 'art', 'name' => 'foo']);
        $this->assertEquals('Not a valid work of art!', $this->runCommand()->fetchTrimmedOutput());
    }
}
