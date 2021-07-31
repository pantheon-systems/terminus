<?php

namespace Pantheon\Terminus\Tests\Traits;

/**
 * Trait LoginHelperTrait
 *
 * @package Pantheon\Terminus\Tests\Traits
 */
trait LoginHelperTrait
{
    /**
     * @setup
     */
    public function setUp(): void
    {
        $token = getenv("TERMINUS_TOKEN");
        if ($token) {
            static::callTerminus("auth:login --machine-token={$token}");
        }
    }
}
