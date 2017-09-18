<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Workflow
 * @package Pantheon\Terminus\Models
 */
class Workflow extends TerminusModel implements ContainerAwareInterface, SessionAwareInterface
{
    use ContainerAwareTrait;
    use SessionAwareTrait;

    public static $pretty_name = 'workflow';
    /**
     * @var TerminusModel
     */
    private $owner;
    /**
     * @var WorkflowOperations
     */
    private $workflow_operations;

    // @TODO: Make this configurable.
    const POLLING_PERIOD = 3;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     * @return Workflow
     * @throws TerminusException
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        if (isset($options['environment'])) {
            $this->owner = $options['environment'];
        } elseif (isset($options['organization'])) {
            $this->owner = $options['organization'];
        } elseif (isset($options['site'])) {
            $this->owner = $options['site'];
        } elseif (isset($options['user'])) {
            $this->owner = $options['user'];
        } else {
            try {
                $this->owner = $options['collection']->getOwnerObject();
            } catch (\Exception $e) {
                throw new TerminusException('Could not locate an owner for this Workflow object.');
            }
        }
    }


    /**
     * Check on the progress of a workflow. This can be called repeatedly and will apply a polling
     * period to prevent flooding the API with requests.
     *
     * @return bool Whether the workflow is finished or not
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function checkProgress()
    {
        // Fetch the workflow status from the API.
        $this->fetch();
        if ($this->isFinished()) {
            // If the workflow failed then figure out the correct output message and throw an exception.
            if (!$this->isSuccessful()) {
                throw new TerminusException($this->getMessage());
            }
            return true;
        }
        return false;
    }

    /**
     * @return WorkflowOperations
     */
    public function getOperations()
    {
        if (empty($this->workflow_operations)) {
            $this->workflow_operations = $this->getContainer()->get(
                WorkflowOperations::class,
                [['data' => $this->get('operations'),],]
            );
        }
        return $this->workflow_operations;
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
            case Environment::class:
                $this->url = "sites/{$owner->getSite()->id}/workflows/{$this->id}";
                break;
            case Organization::class:
                $this->url = "users/{$this->session()->getUser()->id}/organizations/{$owner->id}/workflows/{$this->id}";
                // @TODO: This should be passed in rather than read from the current session.
                break;
            case Site::class:
                $this->url = "sites/{$owner->id}/workflows/{$this->id}";
                break;
            case User::class:
                $this->url = "users/{$owner->id}/workflows/{$this->id}";
                break;
        }
        return $this->url;
    }

    /**
     * Re-fetches workflow data hydrated with logs
     *
     * @return Workflow
     */
    public function fetchWithLogs()
    {
        $options = ['query' => ['hydrate' => 'operations_with_logs',],];
        $this->fetch($options);
        return $this;
    }

    /**
     * Get the success message of a workflow or throw an exception of the workflow failed.
     *
     * @return string The message to output to the user
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getMessage()
    {
        if (!$this->isSuccessful()) {
            $message = 'Workflow failed.';
            $final_task = $this->get('final_task');
            if (!empty($final_task) && !empty($final_task->reason)) {
                $message = $final_task->reason;
            } elseif (!empty($final_task) && !empty($final_task->messages)) {
                foreach ($final_task->messages as $message) {
                    $message = $message->message;
                    if (!is_string($message)) {
                        $message = print_r($message, true);
                    }
                }
            }
        } else {
            $message = $this->get('active_description');
        }
        return $message;
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
     * Returns the status of this workflow
     *
     * @return string
     */
    public function getStatus()
    {
        $status = 'running';
        if ($this->isFinished()) {
            $status = 'failed';
            if ($this->isSuccessful()) {
                $status = 'succeeded';
            }
        }
        return $status;
    }

    /**
     * Detects if the workflow has finished
     *
     * @return bool True if workflow has finished
     */
    public function isFinished()
    {
        return $this->has('result');
    }

    /**
     * Detects if the workflow was successful
     *
     * @return bool True if workflow succeeded
     */
    public function isSuccessful()
    {
        return $this->get('result') == 'succeeded';
    }

    /**
     * Returns a list of WorkflowOperations for this workflow
     *
     * @return WorkflowOperation[]
     * @deprecated 1.5.1-dev Use $this->getOperations->all() for equivalent functionality
     */
    public function operations()
    {
        return $this->getOperations()->all();
    }

    /**
     * Formats workflow object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        $user = 'Pantheon';
        if (isset($this->get('user')->email)) {
            $user = $this->get('user')->email;
        }
        if (is_null($elapsed_time = $this->get('total_time'))) {
            $elapsed_time = time() - $this->get('created_at');
        }

        return [
            'id' => $this->id,
            'env' => $this->get('environment'),
            'workflow' => $this->get('description'),
            'user' => $user,
            'status' => $this->getStatus(),
            'time' => sprintf('%ds', $elapsed_time),
            'finished_at' => $this->get('finished_at'),
            'started_at' => $this->get('started_at'),
            'operations' => $this->getOperations()->serialize(),
        ];
    }

    /**
     * Waits on this workflow to finish
     *
     * @deprecated 1.0.0 Use while($workflow->checkProgress) instead
     *
     * @return Workflow|void
     * @throws TerminusException
     *
     * @deprecated 1.0.1 Use checkProgress to wait on workflows
     */
    public function wait()
    {
        while (!$this->isFinished()) {
            $this->fetch();
            sleep(self::POLLING_PERIOD);
            /**
             * TODO: Output this to stdout so that it doesn't get mixed with any
             *   actual output. We can't use the logger here because that might be
             *   redirected to a log file where each line is timestamped.
             */
            fwrite(STDERR, '.');
        }
        echo "\n";
        if ($this->isSuccessful()) {
            return $this;
        } else {
            $final_task = $this->get('final_task');
            if (($final_task != null) && !empty($final_task->messages)) {
                foreach ($final_task->messages as $data => $message) {
                    if (!is_string($message->message)) {
                        $message->message = print_r($message->message, true);
                    }
                    throw new TerminusException((string)$message->message);
                }
            }
        }
    }

    /**
     * Determines whether this workflow was created after a given datetime
     *
     * @param string $timestamp
     * @return boolean
     */
    public function wasCreatedAfter($timestamp)
    {
        return $this->get('created_at') > $timestamp;
    }

    /**
     * Determines whether this workflow finished after a given datetime
     *
     * @param string $timestamp
     * @return boolean
     */
    public function wasFinishedAfter($timestamp)
    {
        return $this->get('finished_at') > $timestamp;
    }
}
