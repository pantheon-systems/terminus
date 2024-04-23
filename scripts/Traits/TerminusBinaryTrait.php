<?php

namespace Pantheon\Terminus\CI\Traits;

/**
 *
 */
trait TerminusBinaryTrait
{
    /**
     * @return string
     */
    final protected function getProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return string
     */
    final public function getTerminusBinary(): string
    {
        return $this->getProjectRoot() . DIRECTORY_SEPARATOR . 'terminus.phar';
    }
}
