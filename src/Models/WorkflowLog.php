<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Workflow
 *
 * @package Pantheon\Terminus\Models
 */
class WorkflowLog extends TerminusModel
{
    /**
     * @var string
     */
    public const PRETTY_NAME = 'workflow log entry';
    /**
     * @var int
     */
    protected const REFRESH_INTERVAL = 15;
    /**
     * @var string|mixed
     */
    public ?string $kind;
    /**
     * @var WorkflowLogActor
     */
    public WorkflowLogActor $actor;
    /**
     * @var WorkflowLogInfo
     */
    public WorkflowLogInfo $workflow;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/logs/workflows/{id}';

    /**
     * @param $attributes
     * @param array $options
     * @throws TerminusException
     */
    public function __construct($attributes = null, array $options = [])
    {
        if (isset($options['collection'])) {
            $this->collection = $options['collection'];
        }
        try {
            parent::__construct($attributes, $options);
            $this->actor = new WorkflowLogActor((object)$attributes->actor);
            $this->workflow = new WorkflowLogInfo((object)$attributes->workflow);
            $this->kind = $attributes->kind ?? '';
            $this->id = $this->workflow->id ?? '';
        } catch (\Exception $e) {
            throw new TerminusException(
                "Exception unpacking workflow Logs: {message} {data}",
                ['message' => $e->getMessage(), 'data' => print_r($attributes, true)]
            );
        }
    }

    /**
     * @return array
     */
    public function serialize()
    {
        $interval = $this->workflow->finished_at->diff($this->workflow->started_at->getTimestamp());

        return [
            "id"          => $this->workflow->id,
            "env"         => $this->workflow->environment,
            "workflow"    => $this->workflow->type,
            'user'        => $this->actor->name,
            'status'      => $this->workflow->status,
            'finished'    => $this->isFinished() ? "Yes" : "No",
            // if this is not finished, then success should be "?"
            'successful'  => ($this->isFinished() === false) ? '?' : ($this->isSuccessful() ? 'Yes' : 'No'),
            'started_at'  => $this->workflow->started_at->format('Y-m-d H:i:s'),
            'finished_at' => $this->workflow->finished_at->format('Y-m-d H:i:s'),
            'time'        => (string)$interval,
        ];
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->workflow->isFinished();
    }

    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->workflow->isSuccessful();
    }


    /**
     * Wait for the workflow to complete and return success/failure as bool.
     *
     * @return bool
     * @throws TerminusException
     */
    public function waitForComplete($max = 600): bool // 600 = 10 minutes
    {
        $start = time();
        $this->workflow->fetch();
        while (!$this->isFinished() && (time() - $start) < $max) {
            sleep(self::REFRESH_INTERVAL);
            $this->workflow->fetch();
        }
        return $this->isSuccessful();
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        if (isset($this->workflow->{$name})) {
            return $this->workflow->{$name};
        }
        if (isset($this->actor->{$name})) {
            return $this->actor->{$name};
        }
        return null;
    }

    public function get($attribute)
    {
        return $this->__get($attribute);
    }

    /**
     * @param int $time
     * @return bool
     */
    public function startedBefore(\DateTime $dateTime): bool
    {
    }

    /**
     * @param int $time
     * @return bool
     */
    public function startedAfter(int $time): bool
    {
        return $this->workflow->started_at > $time;
    }

    /**
     * @param int $time
     * @return bool
     */
    public function finishedBefore(int $time): bool
    {
        return $this->workflow->finished_at < $time;
    }

    /**
     * @param int $time
     * @return bool
     */
    public function finishedAfter(int $time): bool
    {
        return $this->workflow->finished_at > $time;
    }
}
