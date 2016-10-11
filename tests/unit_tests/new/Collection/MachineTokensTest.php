<?php


namespace Pantheon\Terminus\UnitTests\Collection;

class MachineTokensTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/machine_tokens';
    protected $class = 'Pantheon\Terminus\Collections\MachineTokens';
}
