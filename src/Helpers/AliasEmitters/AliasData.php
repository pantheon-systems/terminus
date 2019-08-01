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

    /**
     * AliasData constructor
     *
     * @param string $site_name
     *   Name of the site.
     * @param string $env_name
     *   Name of the environment.
     * @param string $site_id
     *   The site UUID.
     * @param string $db_password
     *   The database password.
     * @param string $db_port
     *   The database port.
     */
    public function __construct($site_name, $env_name, $site_id, $db_password = '', $db_port = '')
    {
        $this->site_name = $site_name;
        $this->env_name = $env_name;
        $this->site_id = $site_id;
        $this->db_password = $db_password;
        $this->db_port = $db_port;
    }

    /**
     * Returns the site name.
     *
     * @return string
     */
    public function siteName()
    {
        return $this->site_name;
    }

    /**
     * Set the site name.
     *
     * @param string $site_name
     *
     * @return $this
     */
    public function setSiteName($site_name)
    {
        $this->site_name = $site_name;

        return $this;
    }

    /**
     * Get the environment name.
     *
     * @return string
     */
    public function envName()
    {
        return $this->env_name;
    }

    /**
     * Get the environment label.
     *
     * @return string
     */
    public function envLabel()
    {
        if ($this->env_name == '*') {
            return '${env-name}';
        }
        return $this->env_name;
    }

    /**
     * Set the environment name
     *
     * @param string $env_name
     *
     * @return $this
     */
    public function setEnvName($env_name)
    {
        $this->env_name = $env_name;

        return $this;
    }

    /**
     * Return the site id
     *
     * @return string
     */
    public function siteId()
    {
        return $this->site_id;
    }

    /**
     * Sets the site id
     *
     * @param string $site_id
     *
     * @return $this
     */
    public function setSiteId($site_id)
    {
        $this->site_id = $site_id;

        return $this;
    }

    /**
     * Returns the db password.
     *
     * @return string
     */
    public function dbPassword()
    {
        return $this->db_password;
    }

    /**
     * Returns true if the db password has been set.
     *
     * @return bool
     */
    public function hasDbPassword()
    {
        return !empty($this->dbPassword());
    }

    /**
     * Set the db password
     *
     * @param string $db_password
     *
     * @return $this
     */
    public function setDbPassword($db_password)
    {
        $this->db_password = $db_password;

        return $this;
    }

    /**
     * Returns the db port.
     *
     * @return string
     */
    public function dbPort()
    {
        return $this->db_port;
    }

    /**
     * Set the db port
     *
     * @param string $db_port
     *
     * @return $this
     */
    public function setDbPort($db_port)
    {
        $this->db_port = $db_port;

        return $this;
    }

    /**
     * Returns a set of replacements to inject into an alias template.
     *
     * @return array
     *   Associative array of replacements => values.
     */
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
     *
     * @return int
     *   Relative value for greater / equal / less than.
     */
    public function compareNames($rhs)
    {
        return strnatcmp($this->sortOrderId(), $rhs->sortOrderId());
    }

    /**
     * Return an id for this alias data such that sites will sort together
     * with live, test and dev environments appearing together above all of
     * the other multidev domains.
     *
     * @return string
     *   A string representing the sort order for the alias.
     */
    public function sortOrderId()
    {
        return $this->siteName() . ' ' . static::sortOrderPrefix($this->envName()) . ' ' . $this->envName();
    }

    /**
     * Add a prefix in front of the provided environment name to ensure
     * that 'live', 'test' and 'dev' always sort to the top of the list.
     *
     * @param string $name
     *   Environment name for sort order.
     *
     * @return string
     *   A relative order for the group the alias record should appear in.
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
