<?php

namespace Pantheon\Terminus\Models;

/**
 *
 */
class WorkflowLogActor
{
    /**
     * @var string
     */
    public const PRETTY_NAME = 'WorkflowLog Actor';

    /**
     * @var string
     */
    public ?string $id;
    /**
     * @var string
     */
    public ?string $email;
    /**
     * @var string
     */
    public ?string $kind;
    /**
     * @var string
     */
    public ?string $name;


    /**
     * @param $data
     */
    public function __construct($data)
    {
        $this->id = $data->id ?? null;
        $this->email = $data->email ?? null;
        $this->kind = $data->kind ?? null;
        $this->name = $data->name ?? null;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'kind' => $this->kind,
            'name' => $this->name,
        ];
    }
}
