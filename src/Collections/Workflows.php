<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;

/**
 * Class Workflows
 * @package Pantheon\Terminus\Collections
 */
class Workflows extends APICollection implements SessionAwareInterface
{
    use SessionAwareTrait;

    public const PRETTY_NAME = 'workflows';
    /**
     * @var string
     */
    protected $collected_class = Workflow::class;
    /**
     * @var TerminusModel
     */
    private $owner;

    /**
     * Instantiates the collection, sets param members as properties
     *
     * @param array $options Options with which to configure this collection
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if (isset($options['environment'])) {
            $this->owner = $options['environment'];
        } elseif (isset($options['organization'])) {
            $this->owner = $options['organization'];
        } elseif (isset($options['site'])) {
            $this->owner = $options['site'];
        } elseif (isset($options['user'])) {
            $this->owner = $options['user'];
        }
    }

    /**
     * Returns all existing workflows that match given environment id and status.
     *
     * @return Workflow[]
     */
    public function allByEnvironmentIdAndStatus($environment_id, $status)
    {
        $status_map = [
            'finished' => 'isFinished',
            'unfinished' => 'isUnfinished',
            'successful' => 'isSuccessful',
            'failed' => 'isFailed',
        ];
        if (!isset($status_map[$status])) {
            throw new TerminusException('Invalid status "{status}".', compact('status'));
        }
        $status_method = $status_map[$status];
        return array_filter(
            $this->all(),
            function ($workflow) use ($environment_id, $status_method) {
                return $workflow->getEnvironmentId() == $environment_id && $workflow->$status_method();
            }
        );
    }

    /**
     * Returns all existing workflows that match given environment id.
     *
     * @return Workflow[]
     */
    public function allByEnvironmentId($environment_id)
    {
        return array_filter(
            $this->all(),
            function ($workflow) use ($environment_id) {
                return $workflow->getEnvironmentId() == $environment_id;
            }
        );
    }

    /**
     * Returns all existing workflows that have finished
     *
     * @return Workflow[]
     */
    public function allFinished()
    {
        return array_filter(
            $this->all(),
            function ($workflow) {
                return $workflow->isFinished();
            }
        );
    }

    /**
     * Returns all existing workflows that have not finished
     *
     * @return Workflow[]
     */
    public function allUnfinished()
    {
        return array_filter(
            $this->all(),
            function ($workflow) {
                return $workflow->isUnfinished();
            }
        );
    }

    /**
     * Returns all existing workflows that have succeeded
     *
     * @return Workflow[]
     */
    public function allSuccessful()
    {
        return array_filter(
            $this->all(),
            function ($workflow) {
                return $workflow->isSuccessful();
            }
        );
    }

    /**
     * Returns all existing workflows that have failed
     *
     * @return Workflow[]
     */
    public function allFailed()
    {
        return array_filter(
            $this->all(),
            function ($workflow) {
                return $workflow->isFailed();
            }
        );
    }

    /**
     * Returns all existing workflows that contain logs
     *
     * @return Workflow[]
     */
    public function allWithLogs()
    {
        return array_filter(
            $this->allFinished(),
            function ($workflow) {
                return $workflow->get('has_operation_log_output');
            }
        );
    }

    /**
     * Creates a new workflow and adds its data to the collection
     *
     * @param string $type Type of workflow to create
     * @param array $options Additional information for the request, with the
     *   following possible keys:
     *   - environment: string
     *   - params: associative array of parameters for the request
     * @return Workflow $model
     */
    public function create($type, array $options = []): Workflow
    {

        $params = $options['params'] ?? [];
        $results = $this->request()->request(
            $this->getUrl(),
            [
                'method' => 'post',
                'form_params' => [
                    'type' => $type,
                    'params' => (object)$params,
                ],
            ]
        );
        if ($results->isError()) {
            throw new TerminusException(
                "Workflow Creation Failed: {error}",
                ['error' => $results->getStatusCodeReason()]
            );
        }
        $nickname = \uniqid(__CLASS__ . "-");
        $this->getContainer()->add($nickname, $this->collected_class)
            ->addArguments([
                $results->getData(),
                [
                    'id' => $results->getData()->id,
                    'collection' => $this,
                    'owner' => $this->owner
                ]
            ]);
        $model = $this->getContainer()->get($nickname);
        $this->add($model);
        return $model;
    }

    /**
     * Returns the object which controls this collection
     *
     * @return TerminusModel
     */
    public function getOwnerObject()
    {
        return $this->owner;
    }

    /**
     * Get the URL for this model
     *
     * @return string
     */
    public function getUrl()
    {
        $owner = $this->getOwnerObject();
        switch (get_class($owner)) {
            case Environment::class:
                $this->url = "{$owner->getUrl()}/workflows";
                break;
            case Organization::class:
                $this->url = "{$this->session()->getUser()->getUrl()}/organizations/{$owner->id}/workflows";
                // @TODO: This should be passed in rather than read from the current session.
                break;
            case Site::class:
                $this->url = "sites/{$owner->id}/workflows";
                break;
            case User::class:
                $this->url = "{$owner->getUrl()}/workflows";
                break;
        }
        return $this->url;
    }

    /**
     * Fetches workflow data hydrated with operations
     *
     * @return void
     */
    public function fetchWithOperations()
    {
        $this->setFetchArgs(['query' => ['hydrate' => 'operations']]);
        $this->fetch();
    }

    /**
     * Get most-recent workflow from existing collection that has logs
     *
     * @return Workflow|null
     */
    public function findLatestWithLogs()
    {
        $workflows = $this->allWithLogs();
        usort($workflows, function ($a, $b) {
            return ($a->wasFinishedAfter($b->get('finished_at'))) ? -1 : 1;
        });

        if (count($workflows) > 0) {
            return $workflows[0];
        }
        return null;
    }

    /**
     * Get timestamp of most recently created Workflow
     *
     * @return int|null Timestamp
     */
    public function lastCreatedAt()
    {
        $workflows = $this->all();
        usort($workflows, function ($a, $b) {
            return ($a->wasCreatedAfter($b->get('created_at'))) ? -1 : 1;
        });
        if (!empty($workflows)) {
            $workflow = array_shift($workflows);
            return $workflow->get('created_at');
        }
        return null;
    }

    /**
     * Get timestamp of most recently finished workflow
     *
     * @return int|null Timestamp
     */
    public function lastFinishedAt()
    {
        $workflows = $this->all();
        usort($workflows, function ($a, $b) {
            return ($a->wasFinishedAfter($b->get('finished_at'))) ? -1 : 1;
        });
        if (!empty($workflows)) {
            $workflow = array_shift($workflows);
            return $workflow->get('finished_at');
        }
        return null;
    }
}
