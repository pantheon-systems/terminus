<?php

namespace Pantheon\Terminus\Models;

/**
 * Class WorkflowOperation
 * @package Pantheon\Terminus\Models
 */
class WorkflowOperation extends TerminusModel
{
    public static $pretty_name = 'workflow operation';

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return sprintf(
            "------ %s (%s) ------\n%s",
            $this->description(),
            $this->get('environment'),
            $this->get('log_output')
        );
    }

    /**
     * Formats operation object into a descriptive string
     *
     * @return string
     */
    public function description()
    {
        $description = sprintf(
            'Operation: %s finished in %s',
            $this->get('description'),
            $this->duration()
        );
        return $description;
    }

    /**
     * Formats operation object into an associative array for output
     *
     * @return array
     */
    public function serialize()
    {
        $data = [
            'id' => $this->id,
            'type' => $this->get('type'),
            'description' => $this->get('description'),
            'result' => $this->get('result'),
            'duration' => $this->duration(),
        ];

        if ($this->has('log_output')) {
            $data['log_output'] = $this->get('log_output');
        }

        return $data;
    }

    /**
     * Formats operation duration into a string
     *
     * @return string
     */
    protected function duration()
    {
        if ($this->has('run_time')) {
            $duration = sprintf('%ss', round($this->get('run_time')));
        } else {
            $duration = null;
        }
        return $duration;
    }
}
