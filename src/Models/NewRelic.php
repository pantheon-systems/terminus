<?php

namespace Pantheon\Terminus\Models;

/**
 * Class NewRelic
 * @package Pantheon\Terminus\Models
 */
class NewRelic extends AddOnModel
{

    const PRETTY_NAME = 'New Relic';
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/new-relic';

    /**
     * Disables New Relic
     *
     * @return Workflow
     */
    public function disable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('disable_new_relic_for_site', ['site' => $site->id,]);
    }

    /**
     * Enables New Relic
     *
     * @return Workflow
     */
    public function enable()
    {
        $site = $this->getSite();
        return $site->getWorkflows()->create('enable_new_relic_for_site', ['site' => $site->id,]);
    }

    /**
     * Formats the Backup object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        $this->fetch();
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
