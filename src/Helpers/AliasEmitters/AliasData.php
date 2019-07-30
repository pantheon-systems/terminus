<?php
/**
 * AliasData holds the data needed for one site environment alias.
 */

namespace Pantheon\Terminus\Helpers\AliasEmitters;

class AliasData
{
    protected $site_name;
    protected $env_name;
    protected $site_id;
    protected $db_password;
    protected $db_port;

    public function __construct($site_name, $env_name, $site_id, $db_password = '', $db_port = '')
    {
        $this->site_name = $site_name;
        $this->env_name = $env_name;
        $this->site_id = $site_id;
        $this->db_password = $db_password;
        $this->db_port = $db_port;
    }

    public function siteName()
    {
        return $this->site_name;
    }

    public function setSiteName($site_name)
    {
        $this->site_name = $site_name;
    }

    public function envName()
    {
        return $this->env_name;
    }

    public function envLabel()
    {
        if ($this->env_name == '*') {
            return '${env-name}';
        }
        return $this->env_name;
    }

    public function setEnvName($env_name)
    {
        $this->env_name = $env_name;
    }

    public function siteId()
    {
        return $this->site_id;
    }

    public function setSiteId($site_id)
    {
        $this->site_id = $site_id;
    }

    public function dbPassword()
    {
        return $this->db_password;
    }

    public function hasDbPassword()
    {
        return !empty($this->dbPassword());
    }

    public function setDbPassword($db_password)
    {
        $this->db_password = $db_password;
    }

    public function dbPort()
    {
        return $this->db_port;
    }

    public function setDbPort($db_port)
    {
        $this->db_port = $db_port;
    }

    public function replacements()
    {
        $replacements = [
            '{{site_name}}' => $this->siteName(),
            '{{env_name}}' => $this->envName(),
            '{{env_label}}' => $this->envLabel(),
            '{{site_id}}' => $this->siteId(),
            '{{db_password}}' => $this->dbPassword(),
            '{{db_port}}' => $this->dbPort(),
        ];

        return array_filter($replacements);
    }

    /**
     * Compare this alias against another
     */
    public function compareNames($rhs)
    {
        return strnatcmp($this->sortOrderId(), $rhs->sortOrderId());
    }

    /**
     * Return an id for this alias data such that sites will sort together
     * with live, test and dev environments appearing together above all of
     * the other multidev domains.
     */
    public function sortOrderId()
    {
        return $this->siteName() . ' ' . static::sortOrderPrefix($this->envName()) . ' ' . $this->envName();
    }

    /**
     * Add a prefix in front of the provided environment name to ensure
     * that 'live', 'test' and 'dev' always sort to the top of the list.
     */
    public static function sortOrderPrefix($name)
    {
        if ($name == 'live') {
            return '1';
        }
        if ($name == 'test') {
            return '2';
        }
        if ($name == 'dev') {
            return '3';
        }
        return '4';
    }
}
