<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Commit
 * @package Pantheon\Terminus\Models
 */
class Commit extends TerminusModel
{
    public static $pretty_name = 'commit';

    public function serialize()
    {
        return [
            'datetime' => $this->get('datetime'),
            'author' => $this->get('author'),
            'labels' => implode(', ', $this->get('labels')),
            'hash' => $this->get('hash'),
            'message' => substr(strtr(trim($this->get('message')), ["\n" => ' ', "\t" => ' ']), 0, 50),
        ];
    }
}
