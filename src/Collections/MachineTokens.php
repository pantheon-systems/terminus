<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\MachineToken;

/**
 * Class MachineTokens
 * @package Pantheon\Terminus\Collections
 */
class MachineTokens extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = MachineToken::class;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/machine_tokens';
}
