<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Collections\TerminusCollection;

/**
 * Class RowsOfFieldsInterface
 * @package Pantheon\Terminus\Friends
 */
interface RowsOfFieldsInterface
{
    /**
     * @param TerminusCollection $collection A collection of data to get the data from and display
     * @param array $options Elements as follow
     *        function filter A function to filter the collection with. Uses serialize by default.
     *        string message Message to emit if the collection is empty.
     * @return RowsOfFields Returns a RowsOfFields-type object
     */
    public function getRowsOfFields(TerminusCollection $collection, array $options = []);
}
