<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\MachineToken;

/**
 * Class MachineTokens
 * @package Pantheon\Terminus\Collections
 */
class MachineTokens extends UserOwnedCollection
{
    public static $pretty_name = 'machine tokens';
    /**
     * @var string
     */
    protected $collected_class = MachineToken::class;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/machine_tokens';
}
