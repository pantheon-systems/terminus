<?php

namespace Pantheon\Terminus\Commands\Secret;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\SecretsApi\SecretsApiAwareTrait;
use Pantheon\Terminus\SecretsApi\SecretsApiAwareInterface;
use Pantheon\Terminus\SecretsApi\SecretsApi;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class SecretBaseCommand.
 *
 * Base class for Terminus commands that deal with secrets.
 *
 * @package Pantheon\Terminus\Commands\Secret
 */
abstract class SecretBaseCommand extends TerminusCommand implements SecretsApiAwareInterface, RequestAwareInterface
{
    use SecretsApiAwareTrait;
    use RequestAwareTrait;

    public const REDACTED_VALUE = '[REDACTED]';


    /**
     * Construct function to pass the required dependencies.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setSecretsApi(new SecretsApi());
    }

    protected function setupRequest()
    {
        $this->secretsApi()->setRequest($this->request());
    }

    /**
     * @param array $data Data already serialized (i.e. not a TerminusCollection)
     * @param array $options Elements as follow
     *        string $message Message to emit if the collection is empty.
     *        array $message_options Values to interpolate into the error message.
     *        function $sort A function to sort the data using
     * @return RowsOfFields Returns a RowsOfFields-type object with applied filters
     */
    protected function getTableFromData(array $data, array $options = [])
    {
        if (count($data) === 0) {
            $message = $options['message'];
            $options = $options['message_options'] ?? [];
            $this->log()->warning($message, $options);
        }

        return (new RowsOfFields($data))->addRendererFunction(
            function ($key, $cellData) {
                if ($key == 'value' && !$cellData) {
                    return self::REDACTED_VALUE;
                }
                if ($key == 'scopes') {
                    return implode(', ', $cellData);
                }
                if (($key == 'env-values') || ($key == 'org-values')) {
                    $rows = [];
                    foreach ($cellData as $k => $v) {
                        if ($v) {
                            $rows[] = "$k=$v";
                        } else {
                            $rows[] = "$k=" . self::REDACTED_VALUE;
                        }
                    }
                    return implode(', ', $rows);
                }
                return $cellData;
            }
        );
    }
}
