<?php

namespace Pantheon\Terminus\Commands\Auth;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class WhoamiCommand
 * @package Pantheon\Terminus\Commands\Auth
 */
class WhoamiCommand extends TerminusCommand
{
    /**
     * Displays information about the currently logged-in user.
     *
     * @command auth:whoami
     * @aliases whoami
     *
     * @field-labels
     *     firstname: First Name
     *     lastname: Last Name
     *     email: Email
     *     id: ID
     * @default-string-field email
     * @return PropertyList
     *
     * @usage Displays the email of the logged-in user.
     * @usage --format=table Displays the current session and user's data.
     */
    public function whoAmI()
    {
        if ($this->session()->isActive()) {
            $user = $this->session()->getUser();
            return new PropertyList($user->fetch()->serialize());
        } else {
            $this->log()->notice('You are not logged in.');
        }
    }
}
