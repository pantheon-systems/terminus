<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Class ArtCommand
 * @package Pantheon\Terminus\Commands
 */
class ArtCommand extends TerminusCommand
{
    /**
     * @var string Name of the file
     */
    protected $filename;

    /**
     * Displays Pantheon ASCII artwork
     *
     * @command art
     *
     * @param string $name Name of the artwork to select
     *
     * @usage  <artwork>
     *   Displays the <artwork> artwork
     * @usage 
     *   Displays a random artwork
     */
    public function art($name = '')
    {
        $this->formatFilename($name);
        // If a name wasn't provide we want to only print available items.
        if ($name) {
            $artwork_content = $this->retrieveArt($name);
            $this->io()->text("<comment>{$artwork_content}</comment>");
        }
    }

    /**
     * If the user does not specify the $name parameter, then we will
     * prompt for it here.
     *
     * @hook interact
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $available_art = $this->availableArt();
        $io = new SymfonyStyle($input, $output);
        $art_name = $input->getArgument('name');
        if (!$art_name) {
            $io->title('Available Art');
            $io->listing($available_art);
        }
    }

    /**
     * Return available art
     * @return array
     */
    protected function availableArt()
    {
        // Find all of the art in the assets directory.
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->config->get('assets_dir'))
            ->depth('== 0')
            ->name('*.txt')
            ->sortbyname();

        return array_values(
            array_map(
                function ($file) {
                    return $file->getBasename('.txt');
                },
                (array)$finder->getIterator()
            )
        );
    }

    /**
     * Set the art filename.
     *
     * @param $name
     *
     * @return ArtCommand
     *
     */
    protected function formatFilename($name)
    {
        if ($name == 'random') {
            $name = $this->randomArtName();
        }
        $this->filename = $this->config->get('assets_dir') . "/{$name}.txt";

        return $this;
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
        if (!file_exists($this->filename)) {
            throw new TerminusNotFoundException("There is no source for the requested {name} artwork.", ['name' => $name]);
        }
        return base64_decode(file_get_contents($this->filename));
    }

    /**
     * Feeling lucky? Get a random artwork.
     * @return string
     */
    private function randomArtName()
    {
        $art = $this->availableArt();

        return $art[array_rand($art)];
    }

    /**
     * @return string
     */
    protected function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    protected function setFilename(string $filename)
    {
        $this->filename = $filename;
    }
}
