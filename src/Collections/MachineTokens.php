<?php

namespace Pantheon\Terminus\Collections;

class MachineTokens extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/machine_tokens';

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\MachineToken';
}
