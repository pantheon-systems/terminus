<?php

namespace Pantheon\Terminus\Helpers\Traits;

/**
 * Trait DefaultClonePathTrait
 *
 * @package D9ify\Traits
 */
trait DefaultClonePathTrait
{

    /**
     * @return string
     */
    public function getDefaultClonePathBase()
    {
        // Get path resoltion from default composer file directory
        return dirname(\Composer\Factory::getComposerFile()) . "/local-copies";
    }
}
