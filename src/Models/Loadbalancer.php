<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Loadbalancer
 * @package Pantheon\Terminus\Models
 */
class Loadbalancer extends TerminusModel
{
    public static $pretty_name = 'loadbalancer';

    /**
     * @return boolean
     */
    public function isSSL()
    {
        return !empty($this->get('cert_string'));
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return ['ipv4' => $this->get('ipv4'), 'ipv6' => $this->get('ipv6'),];
    }
}
