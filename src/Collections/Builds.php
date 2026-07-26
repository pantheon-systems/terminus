<?php

namespace Pantheon\Terminus\Collections;

use Consolidation\Config\ConfigAwareTrait;
use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Models\Build;

/**
 * Class Builds.
 *
 * @package Pantheon\Terminus\Collections
 */
class Builds extends TerminusCollection
{
    use ConfigAwareTrait;
    use RequestAwareTrait;

    public const PRETTY_NAME = 'builds';

    /**
     * @var string
     */
    protected $collected_class = Build::class;

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @param array $options
     *   Options to change the requests made. Elements as follows:
     *     string  site_id     UUID of the sites to retrieve builds for
     *     string  environemnt Environment of the sites to retrieve builds for
     *
     * @return \Pantheon\Terminus\Collections\Builds
     */
    public function fetch(array $builds = []): Builds
    {
        foreach ($builds as $id => $model_data) {
            if (!$id && !is_object($model_data)) {
                // Empty model, just skip it.
                continue;
            }
            if (!is_object($model_data)) {
                // This should always be an object, however occasionally it is returning as a string
                // We need more information about what it is and to handle the error
                $model_data_str = print_r($model_data, true);
                $error_maxlength = 250;
                if (is_string($model_data_str) && strlen($model_data_str) > $error_maxlength) {
                    $model_data_str = substr($model_data_str, 0, $error_maxlength) . ' ...';
                }
                $error_message = "Fetch failed {file}:{line} \$model_data expected as object but returned as {type}.";
                $error_message .= "\nUnexpected value: {model_data_str}";
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                $context = [
                    'file' => $trace[0]['file'],
                    'line' => $trace[0]['line'],
                    'type' => gettype($model_data),
                    'model_data_str' => $model_data_str
                ];

                // verbose logging for debugging
                $this->logger->debug($error_message, $context);

                // less information for more user-facing messages, but a problem has occurred and we're skipping this
                // item so we should still surface a user-facing message
                $this->logger->warning("Model data missing for {id}", ['id' => $id,]);

                // skip this item since it lacks useful data
                continue;
            }
            if (!isset($model_data->id)) {
                $model_data->id = $id;
            }
            $this->add($model_data);
        }
        return $this;
    }
}
