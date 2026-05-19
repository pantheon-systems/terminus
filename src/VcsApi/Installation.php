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
     * @var string|null
     */
    protected ?string $hostname;

    /**
     * Constructor.
     *
     * @param string $installation_id
     * @param string $vendor
     * @param string $login_name
     * @param string|null $hostname
     */
    public function __construct(string $installation_id, string $vendor, string $login_name, ?string $hostname = null)
    {
        $this->installationId = $installation_id;
        $this->vendor = $vendor;
        $this->loginName = $login_name;
        $this->hostname = $hostname;
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

    /**
     * Return the hostname.
     *
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname ?? 'github.com';
    }

    public function __toString(): string
    {
        $base = sprintf("%s: %s (%s)", $this->getVendor(), $this->getLoginName(), $this->getInstallationId());
        $hostname = $this->getHostname();
        if ($hostname !== 'github.com') {
            $base .= sprintf(' @ %s', $hostname);
        }
        return $base;
    }
}
