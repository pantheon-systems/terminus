<?php

namespace Pantheon\Terminus\Output;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class TerminusOutput
 * @package Pantheon\Terminus\Output
 */
class TerminusOutput extends ConsoleOutput
{
    const REPLACEMENTS = [
        ' [--]' => '',
        '<site_env>' => '<site>.<env>',
        '[<drush_command>]...' => '-- <command>',
        '[<wp_command>]...' => '-- <command>',
        'site_env' => 'site.env',
    ];

    protected function doWrite($message, $newline)
    {
        return parent::doWrite(
            self::replacePhrases($message),
            $newline
        );
    }

    protected static function replacePhrases($message)
    {
        return str_replace(array_keys(self::REPLACEMENTS), array_values(self::REPLACEMENTS), $message);
    }
}
