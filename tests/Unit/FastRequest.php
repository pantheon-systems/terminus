<?php

namespace Pantheon\Terminus\Tests\Unit;

use Pantheon\Terminus\Request\Request;

/**
 * A Request subclass that skips real sleep()s so retry tests run instantly.
 */
class FastRequest extends Request
{
    public array $sleptSeconds = [];

    protected function sleep(int $seconds): void
    {
        $this->sleptSeconds[] = $seconds;
    }
}
