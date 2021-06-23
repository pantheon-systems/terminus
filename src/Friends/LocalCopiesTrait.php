<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;

/**
 * Class EnvironmentTrait
 * @package Pantheon\Terminus\Friends
 */
trait LocalCopiesTrait
{

    use ConfigAwareTrait;

    public function getLocalCopiesFolder(): string
    {
        $local_copies = $this->getConfig()->get('local_copies');
        if (!is_dir($local_copies)) {
            mkdir($local_copies);
            if (!is_dir($local_copies)) {
                throw new TerminusException(
                    "Cannot create local copies folder in: {folder} ",
                    ['folder' => $local_copies]
                );
            }
        }
        return $local_copies;
    }
}
