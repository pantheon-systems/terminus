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

    public function findLatestFromOptionsArray($options = [
        'type' => null,
        'id' => null,
        'commit_hash' => null,
        'start' => 0,
    ]): ?TerminusModel
    {
        $wfl = $this->latest();

        switch (true) {
            // if we have a match, then just return the WorkflowLog
            case ($wfl->get('type') === $options['type']):
            case ($wfl->get('id') === $options['id']):
            case ($wfl->get('commit_hash') === $options['commit_hash']):
            case ($wfl->get('started_at') === $options['start']):
                return $wfl;

            // It's not a match, so let's try to find the workflow

            // 1. Attempt to find workflow by id
            // if the workflow id is set and the latest workflow is not the required workflow,
            // then find the workflow by id
            case ($options['id']):
                return $this->findLatestByProperty('id', $options['id']);

            // 2. Attempt to find workflow by type
            // if the latest workflow is not of the required type,
            // and the type is set, then find the workflow by type
            case ($options['type']):
                return $this->findLatestByProperty('type', $options['type']);

            // 3. Attempt to find workflow by commit hash
            // if the commit hash is set and the latest workflow is not the required workflow,
            // then find the workflow by commit hash
            case ($options['commit_hash']):
                return $this->findLatestByProperty('commit_hash', $options['commit_hash']);

            // 4. Attempt to find workflow by start time
            // if the start time is set and the latest workflow is not the required workflow,
            // then find the workflow by start time
            // This is the least preferred choice because of inaccuracies in the start time
            // it remains here only to provide compatibility with the previous version
            case ($options['start'] > 0):
                return $this->findLatestByProperty('started_at', $options['start']);

            default:
                // Default just return the latest workflow
                return $wfl;
        }
    }

    /**
     * @return WorkflowLog|null
     */
    public function latest(): ?WorkflowLog
    {
        return $this->models[0];
    }

    /**
     * @param $property
     * @param $value
     * @return TerminusModel|null
     */
    public function findLatestByProperty($property, $value): ?TerminusModel
    {
        foreach ($this->models as $model) {
            if ($property == "id" && $model->id == $value) {
                return $model;
            }
            if ($model->get($property) == $value) {
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
