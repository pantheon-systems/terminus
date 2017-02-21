<?php

namespace Pantheon\Terminus\Commands\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class CreateCommand extends SiteCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Creates a new site.
     *
     * @authorize
     *
     * @command site:create
     *
     * @param string $site_name Site name
     * @param string $label Site label
     * @param string $upstream_id Upstream name or UUID
     * @option org Organization name, label, or ID
     *
     * @usage <site> <label> <upstream> Creates a new site named <site>, human-readably labeled <label>, using code from <upstream>.
     * @usage <site> <label> <upstream> --org=<org> Creates a new site named <site>, human-readably labeled <label>, using code from <upstream>, associated with <organization>.
     */

    public function create($site_name, $label, $upstream_id, $options = ['org' => null,])
    {
        if ($this->sites()->nameIsTaken($site_name)) {
            throw new TerminusException('The site name {site_name} is already taken.', compact('site_name'));
        }

        $workflow_options = [
            'label' => $label,
            'site_name' => $site_name
        ];
        $user = $this->session()->getUser();

        // Locate upstream
        $upstream = $user->getUpstreams()->get($upstream_id);

        // Locate organization
        if (!is_null($org_id = $options['org'])) {
            $org = $user->getOrganizationMemberships()->get($org_id)->getOrganization();
            $workflow_options['organization_id'] = $org->id;
        }

        // Create the site
        $this->log()->notice('Creating a new site...');
        $workflow = $this->sites()->create($workflow_options);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }

        // Deploy the upstream
        if ($site = $this->getSite($workflow->get('waiting_for_task')->site_id)) {
            $this->log()->notice('Deploying CMS...');
            $workflow = $site->deployProduct($upstream->id);
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice('Deployed CMS');
        }
    }
}
