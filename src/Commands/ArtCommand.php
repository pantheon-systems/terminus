<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Finder\Finder;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ArtCommand extends TerminusCommand
{
    /**
     * Displays Pantheon ASCII artwork
     *
     * @command art
     *
     * @param string $name Name of the artwork to select
     * @usage terminus art rocket
     *   Displays the rocket artwork
     */
    public function art($name)
    {
        $artwork_content = $this->retrieveArt($name);
        $this->io()->text("<comment>{$artwork_content}</comment>");
    }

    /**
     * If the user does not specify the $name parameter, then we will
     * prompt for it here.
     *
     * @hook interact
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        $available_art = $this->availableArt();

        $io = new SymfonyStyle($input, $output);
        $art_name = $input->getArgument('name');
        if (!$art_name) {
            $art_name = $io->choice('Select art:', $available_art, 'fist');
            $input->setArgument('name', $art_name);
        }
    }

    /**
     * @param $name
     * @return string
     */
    protected function retrieveArt($name)
    {
        $file_path = $this->config->get('assets_dir') . "/{$name}.txt";
        if ($this->validateAsset($file_path)) {
            $output = base64_decode(file_get_contents($file_path));
        } else {
            $output = "Not a valid work of art!";
        }
        return $output;
    }

    /**
     * Check to see if an asset exists.
     *
     * @param $file_path
     * @return bool
     */
    private function validateAsset($file_path)
    {
        return file_exists($file_path);
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
                (array) $finder->getIterator()
            )
        );
    }
}
