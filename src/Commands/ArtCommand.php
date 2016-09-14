<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Console\Output\OutputInterface;

class ArtCommand extends TerminusCommand
{
    /**
     * Displays Pantheon ASCII artwork
     *
     * @name art
     *
     * @param string $name Name of the artwork to select
     * @usage terminus art rocket
     *   Displays the rocket artwork
     */
    public function art($name) {
        $artwork_content = $this->retrieveArt($name);
        $this->io()->text("<comment>{$artwork_content}</comment>");
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
}
