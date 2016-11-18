<?php
-kjs
namespace Terminus\Models;

use Terminus\Config;

class NewRelic extends TerminusModel
{
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
        return $this->site->workflows->create('disable_new_relic_for_site', ['site' => $this->site->id,]);
    }

    /**
     * Enables New Relic
     *
     * @return Workflow
     */
    public function enable()
    {
        return $this->site->workflows->create('enable_new_relic_for_site', ['site' => $this->site->id,]);
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
            'subscribed' => date(Config::get('date_format'), strtotime($this->get('subscription')->starts_on)),
            'state' => $this->get('primary admin')->state,
        ];
    }
}
