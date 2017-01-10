<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\MachineTokens;

/**
 * Class MachineTokensTest
 * Testing class for Pantheon\Terminus\Collections\MachineTokens
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class MachineTokensTest extends UserOwnedCollectionTest
{
    /**
     * @var string
     */
    protected $class = MachineTokens::class;
    /**
     * @var string
     */
    protected $url = 'users/USERID/machine_tokens';
}
