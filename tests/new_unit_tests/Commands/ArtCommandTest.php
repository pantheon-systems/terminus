<?php
namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\ArtCommand;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtCommandTest extends CommandTestCase
{
    protected $command;

    protected $output;

    protected function setUp()
    {
        parent::setUp();

        // This is hard to mock due to the way it interacts with SymfonyStyle and IO
        $this->output = new BufferedOutput();

        $this->command = new ArtCommand();
        $this->command->setConfig($this->config);
        $this->command->setOutput($this->output);
    }

    /**
     * @test
     */
    public function artCommandPrintsContentsOfFilesInAssetsDirectory()
    {
        $this->command->art('hello');

        $this->assertEquals("Hello World!", trim($this->output->fetch()));
    }

    /**
     * @test
     */
    public function artCommandRejectsFilesNotInAssetsDirectory()
    {
        $this->setExpectedException(
            \Exception::class,
            "There is no source for the requested foo artwork."
        );

        $this->command->art('foo');
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
