<?php

namespace Pantheon\Terminus\Commands\Site;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Models\TerminusModel;
use Consolidation\OutputFormatters\StructuredData\AbstractStructuredList;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class InfoCommand extends SiteCommand
{
    use StructuredListTrait;

    /**
     * Displays a site information.
     *
     * @authorize
     *
     * @command site:info
     *
     * @field-labels
     *     id: ID
     *     name: Name
     *     label: Label
     *     created: Created
     *     framework: Framework
     *     region: Region
     *     organization: Organization
     *     plan_name: Plan
     *     max_num_cdes: Max Multidevs
     *     upstream: Upstream
     *     holder_type: Holder Type
     *     holder_id: Holder ID
     *     owner: Owner
     *     frozen: Is Frozen?
     *     last_frozen_at: Date Last Frozen
     * @return PropertyList
     *
     * @param string $site The name or UUID of a site to retrieve information on
     *
     * @usage <site> Displays <site>'s information.
     */
    public function info($site)
    {
        return $this->getPropertyList($this->sites->get($site));
    }

    /**
     * @param TerminusModel $model A model with data to extract
     * @return PropertyList A PropertyList-type object with applied filters
     */
    public function getPropertyList(TerminusModel $model)
    {
        $list = new PropertyList($model->serialize());
        $list = $this->addBooleanRenderer($list);
        $list = $this->addDatetimeRenderer($list, $model::$date_attributes);
        $list = $this->addFrameworkRenderer($list);
        return $list;
    }

    /**
     * Adds a renderer function to the structured list to format framework when rendering
     *
     * @param AbstractStructuredList $table
     * @return RowsOfFields
     */
    private function addFrameworkRenderer(AbstractStructuredList $list)
    {
        $list->addRendererFunction(
            function ($key, $cell_data) {
                if (!is_numeric($key) && $key === 'framework') {
                    switch ($cell_data) {
                        case 'drupal':
                            return 'Drupal 6 or 7';
                        case 'drupal8':
                            return 'Drupal 8 or later';
                        default:
                            return $cell_data;
                    }
                }
                return $cell_data;
            }
        );
        return $list;
    }
}
