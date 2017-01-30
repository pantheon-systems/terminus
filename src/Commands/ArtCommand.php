<?php

namespace Pantheon\Terminus\Commands;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class ArtCommand
 * @package Pantheon\Terminus\Commands
 */
class ArtCommand extends TerminusCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    protected $available_art = ['druplicon', 'fist', 'hello', 'rocket', 'unicorn', 'wordpress',];

    /**
     * Displays Pantheon ASCII artwork.
     *
     * @command art
     *
     * @param string $name Artwork name
     *
     * @usage Displays the list of available artwork.
     * @usage <artwork> Displays the <artwork> artwork.
     */
    public function art($name = 'random')
    {
        if ($name == 'random') {
            $name = $this->randomArtName();
        }
        return $this->retrieveArt($name);
    }

    /**
     * Set the art filename.
     *
     * @param string $name Name of artwork to get a filename for
     * @return string
     */
    protected function getFilename($name)
    {
        return $this->config->get('assets_dir') . "/$name.txt";
    }

    /**
     * Feeling lucky? Get a random artwork.
     *
     * @return string
     */
    protected function randomArtName()
    {
        return $this->available_art[array_rand($this->available_art)];
    }

    /**
     * Retrieve the contents of an art file.
     *
     * @param $name
     * @return string
     * @throws TerminusNotFoundException
     */
    protected function retrieveArt($name)
    {
        $filename = $this->getFilename($name);
        $local_machine_helper = $this->getContainer()->get(LocalMachineHelper::class);
        if (!$local_machine_helper->getFilesystem()->exists($filename)) {
            throw new TerminusNotFoundException(
                'There is no source for the requested {name} artwork.',
                compact('name')
            );
        }
        return base64_decode($local_machine_helper->readFile($filename));
    }
}
