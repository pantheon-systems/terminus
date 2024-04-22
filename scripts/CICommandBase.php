<?php

namespace Pantheon\Terminus\CI;

use Pantheon\Terminus\CI\Traits\TerminusBinaryTrait;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

/**
 * Class CICommandBase
 * @package Pantheon\Terminus\CI
 * @mixin CIApplication
 *
 * Baseclass for CI commands.
 */
class CICommandBase extends Command
{
    use TerminusBinaryTrait;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
    }


    /**
     * @return CIApplication
     */
    final protected function ci(): CIApplication
    {
        $app = $this->getApplication();
        if (!$app instanceof CIApplication) {
            throw new RuntimeException(
                'These commands can only be used with a a CI application because of the way the application is configured.'
            );
        }
        return $app;
    }


    public function onOutputHandler($type, $buffer)
    {
        echo $buffer;
    }
}
