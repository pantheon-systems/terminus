<?php

namespace Pantheon\Terminus\VcsApi;

class Installation
{
    /**
     * @var string
     */
    protected string $installationId;

    /**
     * @var string
     */
    protected string $vendor;

    /**
     * @var string
     */
    protected string $loginName;

    /**
     * Constructor.
     *
     * @param string $installation_id
     * @param string $vendor
     * @param string $login_name
     */
    public function __construct(string $installation_id, string $vendor, string $login_name)
    {
        $this->installationId = $installation_id;
        $this->vendor = $vendor;
        $this->loginName = $login_name;
    }

    /**
     * Return the installation ID.
     *
     * @return string
     */
    public function getInstallationId(): string
    {
        return $this->installationId;
    }

    /**
     * Return the vendor.
     *
     * @return string
     */
    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Return the login name.
     *
     * @return string
     */
    public function getLoginName(): string
    {
        return $this->loginName;
    }

    public function __toString(): string
    {
        return sprintf("%s: %s (%s)", $this->getVendor(), $this->getLoginName(), $this->getInstallationId());
    }
}
