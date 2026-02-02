<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Enums;

/**
 * Connection modes for Pantheon environments.
 */
enum ConnectionMode: string
{
    case Git = 'git';
    case Sftp = 'sftp';

    /**
     * Returns the workflow name used to enable this connection mode.
     */
    public function workflowName(): string
    {
        return match ($this) {
            self::Git => 'enable_git_mode',
            self::Sftp => 'enable_on_server_development',
        };
    }
}
