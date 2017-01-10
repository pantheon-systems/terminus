<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;

/**
 * Class Workflows
 * @package Pantheon\Terminus\Collections
 */
class Workflows extends TerminusCollection implements SessionAwareInterface
{
    use SessionAwareTrait;

    /**
     * @var mixed
     */
    protected $owner;
    /**
     * @var Environment
     */
    private $environment;
    /**
     * @var Organization
     */
    private $organization;
    /**
     * @var Site
     */
    private $site;
    /**
     * @var User
     */
    private $user;
    /**
     * @var string
     */
    protected $collected_class = Workflow::class;

    /**
     * Instantiates the collection, sets param members as properties
     *
     * @param array $options Options with which to configure this collection
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if (isset($options['environment'])) {
            $this->owner = $this->environment = $options['environment'];
        } elseif (isset($options['organization'])) {
            $this->owner = $this->organization = $options['organization'];
        } elseif (isset($options['site'])) {
            $this->owner = $this->site = $options['site'];
        } elseif (isset($options['user'])) {
            $this->owner = $this->user = $options['user'];
        }
    }

    /**
     * Get the URL for this model
     *
     * @return string
     */
    public function getUrl()
    {
        if (!empty($this->url)) {
            return $this->url;
        }

        // Determine the url based on the workflow owner.
        $owner = $this->getOwnerObject();
        switch (get_class($owner)) {
            case 'Pantheon\Terminus\Models\Environment':
                $this->url = sprintf(
                    'sites/%s/environments/%s/workflows',
                    $owner->site->id,
                    $owner->id
                );
                break;
            case 'Pantheon\Terminus\Models\Organization':
                $this->url = sprintf(
                    'users/%s/organizations/%s/workflows',
                    // @TODO: This should be passed in rather than read from the current session.
                    $this->session()->getUser()->id,
                    $owner->id
                );
                break;
            case 'Pantheon\Terminus\Models\Site':
                $this->url = sprintf(
                    'sites/%s/workflows',
                    $owner->id
                );
                break;
            case 'Pantheon\Terminus\Models\User':
                $this->url = sprintf(
                    'users/%s/workflows',
                    $owner->id
                );
                break;
        }
        return $this->url;
    }

    /**
     * Returns all existing workflows that have finished
     *
     * @return Workflow[]
     */
    public function allFinished()
    {
        $workflows = array_filter(
            $this->all(),
            function ($workflow) {
                $is_finished = $workflow->isFinished();
                return $is_finished;
            }
        );
        return $workflows;
    }

    /**
     * Returns all existing workflows that contain logs
     *
     * @return Workflow[]
     */
    public function allWithLogs()
    {
        $workflows = $this->allFinished();
        $workflows = array_filter(
            $workflows,
            function ($workflow) {
                $has_logs = $workflow->get('has_operation_log_output');
                return $has_logs;
            }
        );

        return $workflows;
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
    public function create($type, array $options = [])
    {
        $options = array_merge(['params' => [],], $options);
        $params = array_merge($this->args, $options['params']);

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

        $model = $this->getContainer()->get(
            $this->collected_class,
            [
                $results['data'],
                ['id' => $results['data']->id, 'collection' => $this,]
            ]
        );
        $this->add($model);
        return $model;
    }

    /**
     * Returns the object which controls this collection
     *
     * @return mixed
     */
    public function getOwnerObject()
    {
        return $this->owner;
    }

    /**
     * Fetches workflow data hydrated with operations
     *
     * @param array $options Additional information for the request
     * @return void
     */
    public function fetchWithOperations($options = [])
    {
        $options = array_merge(
            $options,
            ['fetch_args' => ['query' => ['hydrate' => 'operations',],],]
        );
        $this->fetch($options);
    }

    /**
     * Get most-recent workflow from existing collection that has logs
     *
     * @return Workflow|null
     */
    public function findLatestWithLogs()
    {
        $workflows = $this->allWithLogs();
        usort(
            $workflows,
            function ($a, $b) {
                $a_finished_after_b = $a->get('finished_at') >= $b->get('finished_at');
                if ($a_finished_after_b) {
                    $cmp = -1;
                } else {
                    $cmp = 1;
                }
                return $cmp;
            }
        );

        if (count($workflows) > 0) {
            $workflow = $workflows[0];
        } else {
            $workflow = null;
        }
        return $workflow;
    }

    /**
     * Get timestamp of most recently created Workflow
     *
     * @return int|null Timestamp
     */
    public function lastCreatedAt()
    {
        $workflows = $this->all();
        usort(
            $workflows,
            function ($a, $b) {
                $a_created_after_b = $a->get('created_at') >= $b->get('created_at');
                if ($a_created_after_b) {
                    $cmp = -1;
                } else {
                    $cmp = 1;
                }
                return $cmp;
            }
        );
        if (count($workflows) > 0) {
            $timestamp = $workflows[0]->get('created_at');
        } else {
            $timestamp = null;
        }
        return $timestamp;
    }

    /**
     * Get timestamp of most recently finished workflow
     *
     * @return int|null Timestamp
     */
    public function lastFinishedAt()
    {
        $workflows = $this->all();
        usort(
            $workflows,
            function ($a, $b) {
                $a_finished_after_b = $a->get('finished_at') >= $b->get('finished_at');
                if ($a_finished_after_b) {
                    $cmp = -1;
                } else {
                    $cmp = 1;
                }
                return $cmp;
            }
        );
        if (count($workflows) > 0) {
            $timestamp = $workflows[0]->get('finished_at');
        } else {
            $timestamp = null;
        }
        return $timestamp;
    }
}
