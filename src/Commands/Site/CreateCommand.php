<?php

namespace Pantheon\Terminus\Commands\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Models\Organization;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class CreateCommand extends SiteCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Create a site
     *
     * @authorize
     *
     * @command site:create
     *
     * @param string $site_name Machine name of the site to create (i.e. the slugified name)
     * @param string $label A human-readable label for the new site
     * @param string $upstream_id UUID or name of the upstream to use in creating this site
     * @option string $org UUID or name of the organization to which this site will belong
     *
     * @usage terminus site:create <site> <label> <upstream>
     *   Creates a site with the name <site>, human-readable name <label>, using the upstream <upstream>
     * @usage terminus site:create <site> <label> <upstream> --org=<org>
     *   Creates a site with the name <site>, human-readable name <label>, using the upstream <upstream>, belonging to
     *       the <org> organization
     */

    public function create($site_name, $label, $upstream_id, $options = ['org' => null,])
    {
        $workflow_options = [
            'label' => $label,
            'site_name' => $site_name
        ];
        $user = $this->session()->getUser();

        // Locate upstream
        $upstream = $user->getUpstreams()->get($upstream_id);

        // Locate organization
        if (!is_null($org_id = $options['org'])) {
            $org = $user->getOrganizations()->get($org_id)->fetch();
            $workflow_options['organization_id'] = $org->id;
        }

        // Create the site
        $this->log()->notice('Creating a new site...');
        $workflow = $this->sites->create($workflow_options);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }

        // Deploy the upstream
        if ($site = $this->getSite($site_name)) {
            $this->log()->notice('Deploying CMS...');
            $workflow = $site->deployProduct($upstream->id);
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice('Deployed CMS');
        }
    }
}
