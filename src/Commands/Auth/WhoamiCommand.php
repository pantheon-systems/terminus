<?php

namespace Pantheon\Terminus\Commands\Auth;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Models\Auth;

class WhoamiCommand extends TerminusCommand
{
    /**
     * Displays information about the user currently logged in
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
     * @usage terminus auth:whoami
     *   Responds with the email of the logged-in user
     * @usage terminus auth:whoami --format=table
     *   Responds with the current session and user's data
     * @return AssociativeList
     */
    public function whoAmI()
    {
        $user = $this->session()->getUser();
        if ($user) {
            return new AssociativeList($user->fetch()->serialize());
        } else {
            $this->log()->notice('You are not logged in.');
            return null;
        }
    }
}
