<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class AutocompleteCommand
 * @package Pantheon\Terminus\Commands
 */
class AutocompleteCommand extends TerminusCommand
{
    
    /**
     * Displays Terminus Autocomplete config
     *
     * @command autocomplete
     *
     * @usage Returns the shell autocomplete config.
     */
    public function autocomplete() 
    {
        return $this->retrieveList();
    }

    /**
     * Return the filename.
     *
     * @return string
     */
    protected function getFilename()
    {
        return $this->config->get('assets_dir') . "/autocomplete.txt";
    }

    /**
     * Retrieve the contents of the autocomplete file.
     *
     * @return string
     * @throws TerminusNotFoundException
     */
    protected function retrieveList()
    {
        $filename = $this->getFilename();
        $local_machine_helper = $this->getContainer()->get(LocalMachineHelper::class);
        if (!$local_machine_helper->getFilesystem()->exists($filename)) {
            throw new TerminusNotFoundException(
                'Please generate the autocomplete script using the `composer autocomplete:build` command.'
            );
        }
        return $local_machine_helper->readFile($filename);
    }
}
