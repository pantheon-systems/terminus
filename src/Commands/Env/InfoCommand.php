<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class InfoCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays environment status and configuration.
     *
     * @authorize
     * @interact
     *
     * @command env:info
     *
     * @field-labels
     *     id: ID
     *     created: Created
     *     domain: Domain
     *     locked: Locked
     *     initialized: Initialized
     *     connection_mode: Connection Mode
     *     php_version: PHP Version
     *     drush_version: Drush Version
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     *
     * @usage <site>.<env> Displays status and configuration for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function info($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);

        return $this->getPropertyList($this->getEnv($site_env));
    }

    /**
     * @param TerminusModel $model A model with data to extract
     * @return PropertyList A PropertyList-type object with applied filters
     */
    public function getPropertyList(TerminusModel $model)
    {
        $properties = $model->serialize();
        if ($model->isEvcsSite()) {
            // Remove properties that are not applicable to EVCS sites
            unset($properties['connection_mode']);
        }
        if ($model->getSite()->isNodejs()) {
            // Remove properties that are not applicable to Node.js sites
            unset($properties['drush_version']);
            unset($properties['php_version']);
            unset($properties['locked']);
        }
        return new PropertyList($properties);
    }
}
