<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Enums;

/**
 * Backup element types for Pantheon backups.
 */
enum BackupElement: string
{
    case Code = 'code';
    case Files = 'files';
    case Database = 'database';

    /**
     * Returns the workflow name used to restore this backup element.
     */
    public function workflowName(): string
    {
        return match ($this) {
            self::Code => 'restore_code',
            self::Files => 'restore_files',
            self::Database => 'restore_database',
        };
    }

    /**
     * Creates a BackupElement from user input, handling the 'db' alias.
     */
    public static function fromInput(string $input): ?self
    {
        if ($input === 'db') {
            return self::Database;
        }
        return self::tryFrom($input);
    }

    /**
     * Returns all valid input strings including aliases.
     *
     * @return string[]
     */
    public static function validInputs(): array
    {
        return ['code', 'files', 'database', 'db'];
    }
}
