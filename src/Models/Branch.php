<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Branch
 * @package Pantheon\Terminus\Models
 */
class Branch extends TerminusModel
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
        $this->site = $options['collection']->site;
    }

    /**
     * Deletes this branch from the site
     *
     * @return Workflow
     */
    public function delete()
    {
        return $this->site->getWorkflows()->create(
            'delete_environment_branch',
            ['params' => ['environment_id' => $this->id,],]
        );
    }

    /**
     * Formats the Backup object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return ['id' => $this->id, 'sha' => $this->get('sha'),];
    }
}
