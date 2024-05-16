<?php

namespace Pantheon\Terminus\Collections;

use GuzzleHttp\Exception\GuzzleException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Models\WorkflowLog;
use Pantheon\Terminus\Models\WorkflowOperation;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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

    /**
     * @return WorkflowLog|null
     */
    public function latest(): ?WorkflowLog
    {
        return $this->models[0];
    }

    public function findByProperty($property, $value): ?TerminusModel
    {
        foreach ($this->models as $model) {
            if ($model->get($property) == $value) {
                return $model;
            }
        }
        return null;
    }
}
