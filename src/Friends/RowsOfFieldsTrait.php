<?php

namespace Pantheon\Terminus\Friends;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\TerminusCollection;

/**
 * Class RowsOfFieldsTrait
 * @package Pantheon\Terminus\Friends
 */
trait RowsOfFieldsTrait
{
    /**
     * @param TerminusCollection $collection A collection of data to get the data from and display
     * @param array $options Elements as follow
     *        function filter A function to filter the collection with. Uses serialize by default.
     *        string message Message to emit if the collection is empty.
     *        array $message_options Values to interpolate into the error message.
     * @return RowsOfFields Returns a RowsOfFields-type object
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
        $table = $this->addBooleanRenderer($table);
        $table = $this->addDatetimeRenderer($table, $collection);
        return $table;
    }

    /**
     * Adds a renderer function to the RowsOfFields object to format booleans into strings
     *
     * @param RowsOfFields $table
     * @return RowsOfFields
     */
    private function addBooleanRenderer(RowsOfFields $table)
    {
        $table->addRendererFunction(
            function ($key, $cell_data) {
                if ($cell_data === true) {
                    return 'true';
                } else if ($cell_data === false) {
                    return 'false';
                }
                return $cell_data;
            }
        );
        return $table;
    }

    /**
     * Adds a renderer function to the RowsOfFields object to format datetimes when rendering
     *
     * @param RowsOfFields $table
     * @param TerminusCollection $collection
     * @return RowsOfFields
     */
    private function addDatetimeRenderer(RowsOfFields $table, TerminusCollection $collection)
    {
        $config = $this->getConfig();
        $date_attributes = $collection->getCollectedClass()::DATE_ATTRIBUTES;

        $table->addRendererFunction(
            function ($key, $cell_data) use ($config, $date_attributes) {
                if (in_array($key, $date_attributes)) {
                    return $config->formatDatetime($cell_data);
                }
                return $cell_data;
            }
        );
        return $table;
    }
}
