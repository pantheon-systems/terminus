<?php

namespace Pantheon\Terminus\Friends;

use Consolidation\OutputFormatters\StructuredData\AbstractStructuredList;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class StructuredListTrait
 * @package Pantheon\Terminus\Friends
 */
trait StructuredListTrait
{
    /**
     * @param TerminusModel $model A model with data to extract
     * @return PropertyList A PropertyList-type object with applied filters
     */
    public function getPropertyList(TerminusModel $model)
    {
        $list = new PropertyList($model->serialize());
        $list = $this->addBooleanRenderer($list);
        $list = $this->addDatetimeRenderer($list, $model::DATE_ATTRIBUTES);
        return $list;
    }

    /**
     * @param TerminusCollection $collection A collection of models to get the data from
     * @param array $options Elements as follow
     *        function filter A function to filter the collection with. Uses serialize by default.
     *        string message Message to emit if the collection is empty.
     *        array $message_options Values to interpolate into the error message.
     * @return RowsOfFields Returns a RowsOfFields-type object with applied filters
     */
    public function getRowsOfFields(TerminusCollection $collection, array $options = [])
    {
        if (isset($options['filter'])) {
            $filter = $options['filter'];
        } else {
            $filter = function ($collection_argument) {
                return $collection_argument->serialize();
            };
        }
        $data = $filter($collection);
        if (count($data) === 0) {
            $message = isset($options['message'])
                ? $options['message']
                : 'You have no ' . $collection::PRETTY_NAME . '.';
            $options = isset($options['message_options']) ? $options['message_options'] : [];
            $this->log()->warning($message, $options);
        }

        $table = new RowsOfFields($data);
        $date_attributes = $collection->getCollectedClass()::DATE_ATTRIBUTES;
        $table = $this->addBooleanRenderer($table);
        $table = $this->addDatetimeRenderer($table, $date_attributes);
        return $table;
    }

    /**
     * Adds a renderer function to the RowsOfFields object to format booleans into strings
     *
     * @param AbstractStructuredList $table
     * @return AbstractStructuredList
     */
    private function addBooleanRenderer(AbstractStructuredList $list)
    {
        $list->addRendererFunction(
            function ($key, $cell_data) {
                if ($cell_data === true) {
                    return 'true';
                } else if ($cell_data === false) {
                    return 'false';
                }
                return $cell_data;
            }
        );
        return $list;
    }

    /**
     * Adds a renderer function to the structured list to format datetimes when rendering
     *
     * @param AbstractStructuredList $table
     * @param array $date_attributes
     * @return RowsOfFields
     */
    private function addDatetimeRenderer(AbstractStructuredList $list, array $date_attributes)
    {
        $config = $this->getConfig();

        $list->addRendererFunction(
            function ($key, $cell_data) use ($config, $date_attributes) {
                if (in_array($key, $date_attributes)) {
                    return $config->formatDatetime($cell_data);
                }
                return $cell_data;
            }
        );
        return $list;
    }
}
