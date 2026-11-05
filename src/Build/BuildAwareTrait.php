<?php

namespace Pantheon\Terminus\Build;

use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Builds;

/**
 * Class BuildAwareTrait.
 *
 * Implements the BuildAwareInterface for dependency injection of the Builds collection.
 *
 * @package Pantheon\Terminus\Build
 */
trait BuildAwareTrait
{
    use ContainerAwareTrait;

    /**
     * @var Builds
     */
    protected $builds;

    /***
     * @param Builds $builds
     * @return void
     */
    public function setBuilds(Builds $builds)
    {
        $this->builds = $builds;
    }

    /**
     * @return Builds
     *   The builds' collection for the authenticated user.
     */
    public function builds(): Builds
    {
        if (!$this->builds) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Builds::class)
                ->addArgument(['builds' => $this]);
            $this->builds = $this->getContainer()->get($nickname);
        }

        return $this->builds;
    }
}
