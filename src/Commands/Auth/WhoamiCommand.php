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
     * Display information about the currently logged-in user
     *
     * @command auth:whoami
     * @aliases whoami
     *
     * @field-labels
     *   firstname: First Name
     *   lastname: Last Name
     *   email: eMail
     *   id: ID
     * @default-string-field email
     * @return PropertyList
     *
     * @usage terminus auth:whoami
     *   Responds with the email of the logged-in user
     * @usage terminus auth:whoami --format=table
     *   Responds with the current session and user's data
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
