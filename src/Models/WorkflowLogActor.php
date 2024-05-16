<?php

namespace Pantheon\Terminus\Models;

/**
 *
 */
class WorkflowLogActor
{
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


    public function __construct($data)
    {
        $this->id = $data->id;
        $this->email = $data->email;
        $this->kind = $data->kind;
        $this->name = $data->name;
    }

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
