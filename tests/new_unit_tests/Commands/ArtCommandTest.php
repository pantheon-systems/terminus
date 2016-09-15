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
    public function art_command_prints_contents_of_files_in_assets_directory()
    {
        $this->assertEquals('Hello World!', $this->runCommand()->fetchTrimmedOutput());
    }

    /**
     * @test
     */
    public function art_command_rejects_files_not_in_assets_directory()
    {
        $this->setInput(['command' => 'art', 'name' => 'foo']);
        $this->assertEquals('Not a valid work of art!', $this->runCommand()->fetchTrimmedOutput());
    }
}
