<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Filesystem\Filesystem;

class AliasesCommand extends TerminusCommand
{
    /**
     * Generates Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     *
     * @authorized
     *
     * @command aliases
     * @aliases drush:aliases
     *
     * @option boolean $print Print aliases only
     * @option string $location Path and filename; default: ~/.drush/pantheon.aliases.drushrc.php will be used
     *
     * @return string|null
     *
     * @usage terminus aliases
     *     Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to ~/.drush/pantheon.aliases.drushrc.php.
     * @usage terminus aliases --print
     *     Displays Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     * @usage terminus aliases --location=<full_path>
     *     Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to <full_path>.
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
