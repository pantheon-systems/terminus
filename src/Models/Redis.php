<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Redis
 * @package Pantheon\Terminus\Models
 */
class Redis extends AddOnModel
{
    const PRETTY_NAME = 'Redis';

    /**
     * Clears the Redis cache on the named environment
     *
     * @param Environment $env An object representing the environment on which to clear the Redis cache
     * @return Workflow
     */
    public function clear(Environment $env)
    {
        // @Todo: Change this when the env model conversion is merged
        return $env->getWorkflows()->create('clear_redis_cache');
    }

    /**
     * Disables Redis caching
     *
     * @return Workflow
     */
    public function disable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('disable_addon', [
            'params' => [
                'addon' => 'cacheserver',
            ]
        ]);
    }

    /**
     * Enables Redis caching
     *
     * @return Workflow
     */
    public function enable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('enable_addon', [
            'params' => [
                'addon' => 'cacheserver',
            ]
        ]);
    }
}
