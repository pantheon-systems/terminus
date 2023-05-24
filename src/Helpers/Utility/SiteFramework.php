<?php

namespace Pantheon\Terminus\Helpers\Utility;

/**
 * SiteFramework class.
 *
 * @package Pantheon\Terminus\Helpers\Utility
 */
class SiteFramework
{
    public const PRETTY_NAME = 'framework';

    /**
     * @var string|null
     */
    private ?string $framework;

    /**
     * SiteFramework constructor.
     *
     * @param string|null $framework
     */
    public function __construct(?string $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Returns TRUE if the site framework is Drupal 8 or Drupal 9.
     *
     * @return bool
     */
    public function isDrupal8Framework(): bool
    {
        return 'drupal8' === $this->framework;
    }

    /**
     * Returns TRUE if the site framework is Drupal 6 or Drupal 7.
     *
     * @return bool
     */
    public function isDrupal7Framework(): bool
    {
        return 'drupal' === $this->framework;
    }

    /**
     * Returns TRUE if the site framework is WordPress or WordPress Network.
     *
     * @return bool
     */
    public function isWordpressFramework(): bool
    {
        return 'wordpress' === $this->framework || 'wordpress_network' === $this->framework;
    }
}
