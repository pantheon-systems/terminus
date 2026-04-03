<?php

namespace Pantheon\Terminus\Build;

use Pantheon\Terminus\Collections\Builds;

/**
 * Interface BuildAwareInterface.
 *
 * Provides an interface for commands that need access to builds.
 *
 * @package Pantheon\Terminus\Build
 */
interface BuildAwareInterface
{
    /***
     * Sets the builds.
     *
     * @param Builds $builds
     */
    public function setBuilds(Builds $builds);

    /**
     * Returns the builds.
     *
     * @return Builds
     *   The builds' collection for the authenticated user.
     */
    public function builds(): Builds;
}
