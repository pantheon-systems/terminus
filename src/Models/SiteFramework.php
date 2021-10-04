<?php

namespace Pantheon\Terminus\Models;

/**
 * SiteFramework class.
 *
 * @package Pantheon\Terminus\Models
 */
class SiteFramework extends TerminusModel
{
    const PRETTY_NAME = 'framework';

    /**
     * Returns TRUE if the site framework is Drupal 8 or Drupal 9.
     *
     * @return bool
     */
    public function isDrupal8Framework(): bool
    {
        return 'drupal8' === $this->get('framework');
    }

    /**
     * Returns TRUE if the site framework is Drupal 6 or Drupal 7.
     *
     * @return bool
     */
    public function isDrupal7Framework(): bool
    {
        return 'drupal' === $this->get('framework');
    }

    /**
     * Returns TRUE if the site framework is WordPress or WordPress Network.
     *
     * @return bool
     */
    public function isWordpressFramework(): bool
    {
        return 'wordpress' === $this->get('framework') || 'wordpress_network' === $this->get('framework');
    }
}
