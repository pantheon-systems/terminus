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
class WorkflowLogsCollection extends SiteOwnedCollection implements \Iterator, \Countable
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
     * @return array
     * @throws GuzzleException
     * @throws TerminusException
     */
    public function getData(): array
    {
        return $this->serialize();
    }

    /**
     * @throws TerminusException
     * @throws GuzzleException
     */
    public function fetch(): array
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
    public function setData(array $data = []): void
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

    /**
     * @param array $options
     * @return TerminusModel|null
     */
    public function findLatestFromOptionsArray(
        array $options = [
            'type' => null,
            'id' => null,
            'target_commit' => null,
            // ignore start_time for now. We're finding by
            // known properties, not by time.
        ]
    ): ?TerminusModel {
        // Attempt to find the latest workflow by ID
        if (isset($options['id']) && $options['id'] !== null) {
            return $this->findLatestByProperty('id', $options['id']);
        }

        // Attempt to find the latest workflow by commit hash
        if (isset($options['target_commit']) && $options['target_commit'] !== null) {
            return $this->findLatestByProperty('target_commit', $options['target_commit']);
        }

        // Attempt to find the latest workflow by type
        if (isset($options['type']) && $options['type'] !== null) {
            return $this->findLatestByProperty('type', $options['type']);
        }

        // Return the latest workflow if no specific matches found
        return null;
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
    public function findLatestByProperty(string $property, $value): ?TerminusModel
    {
        foreach ($this->models as $model) {
            // If the property matches the value, return the model
            if ($value === $model->get($property)) {
                return $model;
            }
        }
        return null;
    }

    /**
     * @param Environment $env
     * @return WorkflowLogsCollection
     */
    public function filterForEnvironment(Environment $env): WorkflowLogsCollection
    {
        return $this->filter(function ($workflow) use ($env) {
            return $workflow->get("environment") === $env->id;
        });
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->models) ?? 0;
    }
}
