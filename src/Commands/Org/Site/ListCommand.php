<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Site
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays the list of sites associated with an organization.
     *
     * @authorize
     *
     * @command org:site:list
     * @aliases org:sites
     *
     * @field-labels
     *     name: Name
     *     id: ID
     *     plan_name: Plan
     *     framework: Framework
     *     owner: Owner
     *     created: Created
     *     tags: Tags
     *     frozen: Is Frozen?
     * @return RowsOfFields
     *
     * @param string $organization Organization name, label, or ID
     * @option plan Plan filter; filter by the plan's label
     * @option plan_not Plan NOT filter; filter by site's NOT of a given plan
     * @option framework Framework filter; filter by site's framework
     * @option hide_frozen Hide frozen toggle; Do not return frozen sites.
     * @option name Name filter; filter by the plan's name. Non-delimited PHP regex accepted.
     * @option string $tag Tag name to filter
     * @option string $tags Comma separated tag names to filter
     * @option string $upstream Upstream name to filter
     *
     * @usage <organization> Displays the list of sites associated with <organization>.
     * @usage <organization> --plan=<plan> Displays the list of sites associated with <organization> having the plan named <plan>.
     * @usage <organization> --plan_not=<plan> Displays the list of sites associated with <organization> WITHOUT the plan named <plan>.
     * @usage <organization> --tag=<tag> Displays the list of sites associated with <organization> that have the <tag> tag.
     * @usage <organization> --tags=<tag1,tag2> Displays the list of sites associated with <organization> that have the <tag1> and <tag2> tags.
     * @usage <organization> --upstream=<upstream> Displays the list of sites associated with <organization> with the upstream having UUID <upstream>.
     * @usage <organization> --name=<regex> Displays a list of accessible sites with a name that matches <regex>.
     * @usage <organization> --framework=wordpress Displays a list of accessible sites with the WordPress framework.
     */
    public function listSites($organization, $options = ['plan' => null, 'plan_not' => null, 'framework' => null, 'hide_frozen' => true, 'name' => null, 'tag' => null, 'tags' => null, 'upstream' => null,])
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $this->sites->fetch(['org_id' => $org->id,]);
        if (isset($options['name']) && !is_null($name = $options['name'])) {
            $this->sites->filterByName($name);
        }
        if (isset($options['plan']) && !is_null($plan = $options['plan'])) {
            $this->sites->filterByPlanName($plan);
        }
        if (isset($options['plan_not']) && !is_null($plan_not = $options['plan_not'])) {
            $this->sites->filterByPlanNameNot($plan_not);
        }
        if (isset($options['framework']) && !is_null($framework = $options['framework'])) {
            $this->sites->filterByFramework($framework);
        }
        if (isset($options['hide_frozen'])) {
            $frozen_status = ! boolval($options['hide_frozen']);
            $this->sites->filterByFrozenStatus($frozen_status);
        }
        if (!is_null($tag = $options['tag'])) {
            $this->sites->filterByTag($tag);
        }
        if (!is_null($tags = $options['tags'])) {
            $tags = explode(',', $tags);
            foreach( $tags as $tag ) {
                $this->sites->filterByTag($tag);
            }
        }
        if (!is_null($upstream = $options['upstream'])) {
            $this->sites->filterByUpstream($upstream);
        }
        return $this->getRowsOfFields(
            $this->sites,
            ['message' => 'This organization has no sites matching the given parameters.',]
        );
    }
}
