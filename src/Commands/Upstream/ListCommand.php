<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Upstream
 */
class ListCommand extends TerminusCommand
{
    use StructuredListTrait;

    /**
     * Displays the list of upstreams accessible to the currently logged-in user.
     *
     * @authorize
     *
     * @command upstream:list
     * @aliases upstreams
     *
     * @field-labels
     *     id: ID
     *     label: Name
     *     machine_name: Machine Name
     *     category: Category
     *     type: Type
     *     framework: Framework
     *     organization: Organization
     * @option all Show all upstreams
     * @option framework Framework filter
     * @option name Name filter
     * @option org Organization filter; "all" or an organization's name, label, or ID
     * @return RowsOfFields
     *
     * @usage Displays the list of upstreams accessible to the currently logged-in user.
     * @usage --all Displays upstreams of all types, including product.
     * @usage --framework=<framework> Displays a list of accessible upstreams using the <framework> framework.
     * @usage --name=<regex> Displays a list of accessible upstreams with a name that matches <regex>.
     */
    public function listUpstreams($options = ['all' => false, 'framework' => null, 'name' => null, 'org' => 'all',])
    {
        $user = $this->session()->getUser();
        if (isset($options['org']) && ($options['org'] !== 'all')) {
            $upstreams = $user->getOrganizationMemberships()->get($options['org'])->getOrganization()->getUpstreams();
        } else {
            $upstreams = $user->getUpstreams();
        }

        $upstreams = $this->filterByFramework($upstreams, $options);
        $upstreams = $this->filterByName($upstreams, $options);
        $upstreams = $this->filterForCoreCustom($upstreams, $options);

        return $this->getRowsOfFields($upstreams, ['sort' => $this->sortFunction($options)]);
    }

    /**
     * @param Upstreams|OrganizationUpstreams $upstreams
     * @param array $options
     */
    protected function filterByFramework($upstreams, $options = [])
    {
        if (isset($options['framework']) && !is_null($framework = $options['framework'])) {
            $upstreams->filter(function ($upstream) use ($framework) {
                return $upstream->get('framework') === strtolower($framework);
            });
        }
        return $upstreams;
    }

    /**
     * @param Upstreams|OrganizationUpstreams $upstreams
     * @param array $options
     */
    protected function filterByName($upstreams, $options = [])
    {
        if (isset($options['name']) && !is_null($name = $options['name'])) {
            $upstreams->filterByName($name);
        }
        return $upstreams;
    }

    /**
     * @param Upstreams|OrganizationUpstreams $upstreams
     * @param array $options
     */
    protected function filterForCoreCustom($upstreams, $options = [])
    {
        if (!(isset($options['all']) && (boolean)$options['all'])) {
            $upstreams->filter(function ($upstream) {
                return in_array($upstream->get('type'), ['core', 'custom',]);
            });
        }
        return $upstreams;
    }

    protected function sortAllTypes()
    {
        return function ($a, $b) {
            return (($type_order = $this->sort('type', $a, $b)) === 0) ? $this->sort('label', $a, $b) : $type_order;
        };
    }

    /**
     * Reverses the type so custom upstreams appear before core
     */
    protected function sortFiltered()
    {
        return function ($a, $b) {
            return (($type_order = $this->sort('type', $a, $b)) === 0) ? $this->sort('label', $a, $b) : -$type_order;
        };
    }

    /**
     * @param array $options
     */
    protected function sortFunction($options = [])
    {
        return (isset($options['all']) && $options['all']) ? $this->sortAllTypes() : $this->sortFiltered();
    }

    /**
     * Sorts arrays by the element indicated in ascending order
     * For use with the PHP usort function
     *
     * @param string $element Key of array elements to compare
     * @param array $a First array to compare
     * @param array $b Second array to compare
     * @return int
     */
    private function sort($element, $a, $b)
    {
        return ($a[$element] < $b[$element]) ? -1 : (($a[$element] > $b[$element]) ? 1 : 0);
    }
}
