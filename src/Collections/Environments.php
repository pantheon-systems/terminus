<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class Environments.
 *
 * @package Pantheon\Terminus\Collections
 */
class Environments extends SiteOwnedCollection
{
    /**
     *
     */
    public const PRETTY_NAME = 'environments';
    /**
     *
     */
    public const DEFAULT_ENVIRONMENTS = ['dev', 'test', 'live'];
    /**
     * @var string
     */
    protected $collected_class = Environment::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments';

    /**
     * Creates a multidev environment
     *
     * @param string $target_env_id Name of new the environment
     * @param Environment $source_env Environment to clone from
     * @param array $options
     *     bool no-db Do not copy database from the source environment
     *     bool no-files Do not copy files from the source environment
     *
     * @return \Pantheon\Terminus\Models\Workflow
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($target_env_id, Environment $source_env, array $options = []): Workflow
    {
        $params = [
            'clone_database' => ['from_environment' => $source_env->id,],
            'clone_files' => ['from_environment' => $source_env->id,],
            'annotation' => "Create the \"{$target_env_id}\" environment.",
        ];
        if (isset($options['no-db']) && $options['no-db']) {
            unset($params['clone_database']);
        }
        if (isset($options['no-files']) && $options['no-files']) {
            unset($params['clone_files']);
        }

        return $this->getSite()->getWorkflows()->create(
            'create_cloud_development_environment',
            [
                'params' => [
                    'environment_id' => $target_env_id,
                    'deploy' => $params,
                ],
            ]
        );
    }

    /**
     * List Environment IDs, with Dev/Test/Live first
     *
     * @return string[] $ids
     */
    public function ids(): array
    {
        $ids = array_keys($this->all());

        //Reorder environments to put dev/test/live first
        // but only if they exist
        $default_ids = array_intersect($ids, self::DEFAULT_ENVIRONMENTS);
        $multidev_ids = array_diff($ids, $default_ids);

        return array_merge($default_ids, $multidev_ids);
    }

    /**
     * Returns a list of all multidev environments on the collection-owning Site
     *
     * @return Environment[]
     */
    public function multidev()
    {
        $multidev_envs = $this->filterForMultidev()->all();
        $this->reset();
        return $multidev_envs;
    }

    /**
     * Filters out non-multidev environments
     *
     * @return Environments $this
     */
    public function filterForMultidev()
    {
        $this->filter(function ($env) {
            return $env->isMultidev();
        });
        return $this;
    }

    /**
     * Retrieves all models serialized into arrays. If the site is frozen, it skips test and live.
     *
     * @return array
     */
    public function serialize()
    {
        $site_is_frozen = $this->getSite()->isFrozen();
        $models = [];
        foreach ($this->all() as $id => $model) {
            if (!$site_is_frozen || !in_array($id, ['test', 'live',])) {
                $models[$id] = $model->serialize();
            }
        }
        return $models;
    }
}
