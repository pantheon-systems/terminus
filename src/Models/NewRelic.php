<?php

namespace Pantheon\Terminus\Models;

use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class NewRelic
 * @package Pantheon\Terminus\Models
 */
class NewRelic extends TerminusModel implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @var Site
     */
    public $site;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->site = $options['site'];
        $this->url = "sites/{$this->site->id}/new-relic";
    }

    /**
     * Disables New Relic
     *
     * @return Workflow
     */
    public function disable()
    {
        return $this->site->getWorkflows()->create('disable_new_relic_for_site', ['site' => $this->site->id,]);
    }

    /**
     * Enables New Relic
     *
     * @return Workflow
     */
    public function enable()
    {
        return $this->site->getWorkflows()->create('enable_new_relic_for_site', ['site' => $this->site->id,]);
    }

    /**
     * Formats the Backup object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        if (empty($name = $this->get('name'))) {
            return [];
        }
        return [
            'name' => $name,
            'status' => $this->get('status'),
            'subscribed' => date($this->getConfig()->get('date_format'), strtotime($this->get('subscription')->starts_on)),
            'state' => $this->get('primary admin')->state,
        ];
    }
}
