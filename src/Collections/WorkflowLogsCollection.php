<?php

namespace Pantheon\Terminus\Collections;

use GuzzleHttp\Exception\GuzzleException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Models\WorkflowLog;

/**
 * Class WorkflowLogsCollection
 * @package Pantheon\Terminus\Collections
 */
class WorkflowLogsCollection extends SiteOwnedCollection implements \Iterator
{
    /**
     *
     */
    public const PRETTY_NAME = 'Workflow Logs Collection';
    /**
     * @var string
     */
    protected $collected_class = WorkflowLog::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/logs/workflows';
    /**
     * @var
     */
    private $current;

    /**
     * @throws TerminusException
     */
    public function __construct(Site $site)
    {
        parent::__construct(["site" => $site]);
    }

    /**
     * @throws TerminusException
     * @throws GuzzleException
     */
    public function fetch()
    {
        $result = $this->request()->request(
            $this->getUrl(),
            ['site_id' => $this->getSite()->id, "method" => "GET"]
        );
        $this->setData($result['data']);
        return $this;
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data = [])
    {
        $class = $this->getCollectedClass();
        foreach ($data as $datum) {
            $this->models[] = new $class($datum, ['collection' => $this]);
        }
    }


    /**
     * @throws TerminusException
     * @throws GuzzleException
     */
    public function serialize()
    {
        return array_map(function ($model) {
            return $model->serialize();
        }, $this->models);
    }

    /**
     * @return mixed|\Pantheon\Terminus\Models\TerminusModel
     */
    public function current(): ?TerminusModel
    {
        return $this->models[$this->current];
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->current++;
    }

    /**
     * @return mixed|null
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->models[$this->current]);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->current = 0;
    }

    public function findLatestFromOptionsArray(
        $options = [
            'type' => null,
            'id' => null,
            'commit_hash' => null,
            'start' => 0,
        ]
    ): TerminusModel {
        $start_time = $options['start'] ?? 0; // Default to 0 if not set

        // Initialize $wfl to the first workflow in the collection
        $wfl = $this->latest();

        // Loop through the collection to find the first workflow newer than the start time
        while ($wfl && intval($start_time) !== 0 && $wfl->get('start_time') > $start_time) {
            $this->next();
            $wfl = $this->current();
        }

        // If the workflow is newer than the start time and matches the options
        if (
            $wfl && $wfl->get('start_time') >= $start_time &&
            (
                $wfl->get('type') === $options['type'] ||
                $wfl->get('commit_hash') === $options['commit_hash']
            )
        ) {
            return $wfl;
        }

        // Attempt to find the latest workflow by type
        if (isset($options['type'])) {
            return $this->findLatestByProperty('type', $options['type'], $start_time);
        }

        // Attempt to find the latest workflow by commit hash
        if (isset($options['commit_hash'])) {
            return $this->findLatestByProperty('commit_hash', $options['commit_hash'], $start_time);
        }

        // Return the latest workflow if no specific matches found
        return $wfl;
    }

    /**
     * @return WorkflowLog|null
     */
    public function latest(): ?WorkflowLog
    {
        return $this->models[0];
    }

    /**
     * @param string $property
     * @param mixed $value
     * @param int $start_time
     * @return TerminusModel|null
     */
    public function findLatestByProperty($property, $value, $start_time = 0): ?TerminusModel
    {
        foreach ($this->models as $model) {
            // Skip if older than start time
            if ($model->get('start_time') < $start_time) {
                continue;
            }
            // If the property matches the value, return the model
            if ($value === $model->get($property)) {
                return $model;
            }
        }
        return null;
    }

    /**
     * @param $env
     * @return WorkflowLogsCollection
     */
    public function filterForEnvironment(Environment $env): WorkflowLogsCollection
    {
        return $this->filter(function ($workflow) use ($env) {
            return $workflow->get("environment") === $env->id;
        });
    }
}
