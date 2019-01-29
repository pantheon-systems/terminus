<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Helpers\LocalMachineHelper;

class AliasesCommand extends TerminusCommand
{
    /**
     * Generates Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     *
     * @authorize
     *
     * @command aliases
     * @aliases drush:aliases
     *
     * @option boolean $print Print aliases only
     * @option string $location Path and filename; default: ~/.drush/pantheon.aliases.drushrc.php will be used
     *
     * @return string|null
     *
     * @usage Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to ~/.drush/pantheon.aliases.drushrc.php.
     * @usage --print Displays Pantheon Drush aliases for sites on which the currently logged-in user is on the team.
     * @usage --location=<full_path> Saves Pantheon Drush aliases for sites on which the currently logged-in user is on the team to <full_path>.
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

        $this->getContainer()->get(LocalMachineHelper::class)->writeFile($location, $aliases);
        $this->log()->notice('Aliases file written to {location}.', ['location' => $location,]);
    }
}
