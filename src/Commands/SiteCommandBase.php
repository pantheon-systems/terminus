<?php

namespace Pantheon\Terminus\Commands;

use Terminus\Collections\Sites;

abstract class SiteCommandBase extends TerminusCommand
{
    /**
     * @var \Terminus\Collections\Sites
     */
    protected $sites;

    /**
     * @param \Terminus\Collections\Sites $sites The sites collection
     */
    public function setSites(Sites $sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return \Terminus\Collections\Sites
     */
    protected function sites()
    {
        // @TODO: This could be injectable using the container
        if (empty($this->sites)) {
            $this->sites = new Sites();
        }
        return $this->sites;
    }

    /**
     * Get a site with the given ID.
     *
     * @param string $site_id The name of the site
     *
     * @return \Terminus\Collections\Site
     * @throws \Terminus\Exceptions\TerminusException
     */
    protected function getSite($site_id)
    {
        return $this->sites()->get($site_id);
    }
}
