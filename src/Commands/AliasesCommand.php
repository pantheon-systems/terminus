<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Filesystem\Filesystem;

class AliasesCommand extends TerminusCommand
{
    /**
     * Print and save Drush aliases for the the sites on which you are a team member
     *
     * @authorized
     *
     * @command aliases
     * @aliases drush:aliases
     *
     * @option boolean $print Print the aliases rather than saving them to a file
     * @option string $location The full path, including file name, to the new alias file being created. Without this,
     *     ~/.drush/pantheon.aliases.drushrc.php will be used
     *
     * @return string|null
     *
     * @usage terminus aliases
     *     Saves your Pantheon Drush aliases to ~/.drush/pantheon.aliases.drushrc.php
     * @usage terminus aliases --print
     *     Prints your Pantheon Drush aliases on your screen
     * @usage terminus aliases --location=<full_path>
     *     Saves your Panthoen Drush aliases to <full_path>
     */
    public function aliases($options = ['print' => false, 'location' => null,])
    {
        $aliases = $this->session()->getUser()->getAliases();
        if (isset($options['print']) && $options['print']) {
            return $aliases;
        }
        if (is_null($location = $options['location'])) {
            $location = '~/.drush/pantheon.aliases.drushrc.php';
        }
        $config = $this->getConfig();
        $location = $config->fixDirectorySeparators(str_replace('~', $config->get('user_home'), $location));

        // @todo, should this dependency be injected somehow?
        $filesystem = new Filesystem();
        try {
            $filesystem->dumpFile($location, $aliases);
            $this->log()->notice('Aliases file written to {location}.', ['location' => $location,]);
        } catch (IOException $e) {
            // @todo, not sure what the error reporting should be here.
        }
    }
}
