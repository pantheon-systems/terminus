<?php
namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\ArtCommand;

class ArtCommandTest extends CommandTestCase
{
    protected $command;

    protected function setUp()
    {
        parent::setUp();
        $this->command = new ArtCommand();
        $this->command->setConfig($this->config);
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
        $this->assertEquals(
            '[error]  There is no source for the requested foo artwork.',
            $this->runCommand()->fetchTrimmedOutput()
        );
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 1
     * @expectedExceptionMessage There is no source for the requested foo artwork.
     */
    public function retrieveArtThrowsExceptionIfInvalidArtName()
    {
        $this->protectedMethodCall($this->command, 'retrieveArt', ['foo']);
    }

    /**
     * @test
     */
    public function formatFilenameAppliesProperFormatting()
    {
        $this->protectedMethodCall($this->command, 'formatFilename', ['foo']);
        $this->assertEquals(
            $this->config->get('assets_dir') . '/foo.txt',
            $this->protectedMethodCall($this->command, 'getFilename', [])
        );
    }

    /**
     * @test
     */
    public function randomArtNameReturnsOneOfTheAvailableArtNames()
    {
        $this->assertContains(
            $this->protectedMethodCall($this->command, 'randomArtName'),
            $this->protectedMethodCall($this->command, 'availableArt')
        );
    }

    /**
     * @test
     */
    public function randomArtNameReturnString()
    {
        $this->assertInternalType(
            'string',
            $this->protectedMethodCall($this->command, 'randomArtName')
        );
    }

    /**
     * @test
     */
    public function availableArtReturnsAnArray()
    {
        $this->assertInternalType(
            'array',
            $this->protectedMethodCall($this->command, 'availableArt')
        );
    }
}
