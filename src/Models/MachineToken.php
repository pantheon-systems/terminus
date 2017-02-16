<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;

/**
 * Class MachineToken
 * @package Pantheon\Terminus\Models
 */
class MachineToken extends TerminusModel implements UserInterface
{
    use UserTrait;

    public static $pretty_name = 'machine token';

    /**
     * Deletes machine token
     * @return void
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function delete()
    {
        $response = $this->request->request(
            "users/{$this->getUser()->id}/machine_tokens/{$this->id}",
            ['method' => 'delete',]
        );
        if ($response['status_code'] !== 200) {
            throw new TerminusException('There was an problem deleting the machine token.');
        }
    }
}
