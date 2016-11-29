<?php

namespace Pantheon\Terminus\UnitTests\Collections;

/**
 * Class MachineTokensTest
 * Testing class for Pantheon\Terminus\Collections\MachineTokens
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class MachineTokensTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/machine_tokens';
    protected $class = 'Pantheon\Terminus\Collections\MachineTokens';
}
