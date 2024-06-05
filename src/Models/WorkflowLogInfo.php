<?php

namespace Pantheon\Terminus\Models;

/**
 *
 */
class WorkflowLogInfo
{
    public const PRETTY_NAME = 'WorkflowLog Info';
    /**
     * @var string|mixed
     */
    public ?string $status;
    /**
     * @var string|mixed
     */
    public ?string $active_description;
    /**
     * @var string|mixed
     */
    public ?string $description;
    /**
     * @var float
     */
    public ?\DateTime $finished_at;
    /**
     * @var string|mixed
     */
    public ?string $reason;
    /**
     * @var float
     */
    public \DateTime $started_at;
    /**
     * @var string|mixed
     */
    public ?string $id;
    /**
     * @var bool
     */
    public bool $has_more_details;
    /**
     * @var string|mixed
     */
    public ?string $environment;
    /**
     * @var int
     */
    public ?int $progress;
    /**
     * @var string|mixed
     */
    public ?string $type;
    public ?string $target_commit;

    /**
     * @param $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->status = $data->status ?? null;
        $this->active_description = $data->active_description ?? null;
        $this->description = $data->description ?? null;
        $this->finished_at = $data->finished_at != 0 ?
            new \DateTime("@" . $data->finished_at) : null;
        $this->reason = $data->reason ?? null;
        $this->started_at = new \DateTime("@" . $data->started_at);
        $this->id = $data->id;
        $this->has_more_details = boolval($data->has_more_details);
        $this->environment = $data->environment ?? null;
        $this->progress = intval($data->progress) ?? null;
        $this->type = $data->type;
        $this->target_commit = $data->target_commit ?? null;
    }

    public function serialize(): object
    {
        return (object) [
            'status' => $this->status,
            'active_description' => $this->active_description,
            'description' => $this->description,
            'finished_at' => $this->finished_at,
            'reason' => $this->reason,
            'started_at' => $this->started_at,
            'id' => $this->id,
            'has_more_details' => $this->has_more_details,
            'environment' => $this->environment,
            'progress' => $this->progress,
            'type' => $this->type,
            'target_commit' => $this->target_commit,
        ];
    }


    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'Success';
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->progress === 100;
    }
}
