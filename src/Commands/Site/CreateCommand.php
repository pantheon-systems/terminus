<?php

namespace Pantheon\Terminus\Commands\Site;

use Terminus\Collections\Upstreams;
use Terminus\Models\Organization;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class CreateCommand extends SiteCommand
{
    /**
     * Creates a site
     *
     * @command site:create
     *
     * @usage terminus site:create <sitename> --label <label> --upstream <upstream>
     *   Creates a site with the listed options.
     *
     * @param string $site_name Machine name of the site to create
     * @param string $label A human-readable label for the new site
     * @param string $upstream_id UUID or name of the upstream product to apply
     * @option string $org UUID or name of the organization to which this site will belong
     *
     * @usage terminus site:create <site> <label> <upstream>
     *   Creates a site with the name, label, and org named.
     */

    public function create($site_name, $label, $upstream_id, $options = ['org' => null,])
    {
        $workflow_options = [
            'label' => $label,
            'site_name' => $site_name
        ];

        // Locate upstream
        $upstreams = new Upstreams();
        $upstream = $upstreams->get($upstream_id);

        // Locate organization
        if (!is_null($id = $options['org'])) {
            $org = new Organization((object)compact('id'));
            $org->fetch();
            $workflow_options['organization_id'] = $org->id;
        }

        // Create the site
        $this->log()->notice('Creating a new site...');
        $workflow = $this->sites->create($workflow_options);
        $workflow->wait();

        // Deploy the upstream
        if ($site = $this->getSite($site_name)) {
            $this->log()->notice('Deploying CMS...');
            $workflow = $site->deployProduct($upstream->id);
            $workflow->wait();
            $this->log()->notice('Deployed CMS');
        }
    }
}
