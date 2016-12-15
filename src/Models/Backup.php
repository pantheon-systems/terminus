<?php

namespace Pantheon\Terminus\Models;

use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Backup
 * @package Pantheon\Terminus\Models
 */
class Backup extends TerminusModel implements ConfigAwareInterface
{
    use ConfigAwareTrait;
    /**
     * @var environment
     */
    public $environment;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->environment = $options['collection']->environment;
    }

    /**
     * Determines whether the backup has been completed or not
     *
     * @return boolean True if backup is completed.
     */
    public function backupIsFinished()
    {
        return (
            ($this->get('size') != 0)
            && (
                ($this->get('finish_time') != null)
                || ($this->get('timestamp') != null)
            )
        );
    }

    /**
     * Returns the bucket name for this backup
     *
     * @return string
     */
    public function getBucket()
    {
        $bucket = 'pantheon-backups';
        if (strpos($this->getConfig()->get('host'), 'onebox') !== false) {
            $bucket = "onebox-$bucket";
        }
        return $bucket;
    }

    /**
     * Returns the date the backup was completed
     *
     * @return string Timestamp completion time or "Pending"
     */
    public function getDate()
    {
        if (!is_null($this->get('finish_time'))) {
            $datetime = $this->get('finish_time');
        } elseif (!is_null($this->get('timestamp'))) {
            $datetime = $this->get('timestamp');
        } else {
            return 'Pending';
        }
        return date($this->getConfig()->get('date_format'), $datetime);
    }

    /**
     * Returns the type of initiator of the backup
     *
     * @return string Either "manual" or "automated"
     */
    public function getInitiator()
    {
        preg_match("/.*_(.*)/", $this->get('folder'), $automation_match);
        return (isset($automation_match[1]) && ($automation_match[1] == 'automated')) ? 'automated' : 'manual';
    }

    /**
     * Returns the size of the backup in MB
     *
     * @return string A number (an integer or a float) followed by 'MB'.
     */
    public function getSizeInMb()
    {
        $size_string = '0';
        if ($this->get('size') != null) {
            $size = $this->get('size') / 1048576;
            if ($size > 0.1) {
                $size_string = sprintf('%.1fMB', $size);
            } elseif ($size > 0) {
                $size_string = '0.1MB';
            }
        }
        return $size_string;
    }

    /**
     * Gets the URL of a backup
     *
     * @return string
     */
    public function getUrl()
    {
        $path = sprintf(
            'sites/%s/environments/%s/backups/catalog/%s/%s/s3token',
            $this->environment->site->id,
            $this->environment->id,
            $this->get('folder'),
            $this->get('type')
        );
        $options  = ['method' => 'post', 'form_params' => ['method' => 'get',],];
        $response = $this->request()->request($path, $options);
        return $response['data']->url;
    }

    /**
     * Restores this backup
     *
     * @return Workflow
     * @throws TerminusException
     */
    public function restore()
    {
        switch ($this->get('type')) {
            case 'code':
                $type = 'restore_code';
                break;
            case 'files':
                $type = 'restore_files';
                break;
            case 'database':
                $type = 'restore_database';
                break;
            default:
                throw new TerminusException('This backup has no archive to restore.');
                break;
        }
        $workflow = $this->environment->getWorkflows()->create(
            $type,
            [
                'params' => [
                    'key' => "{$this->environment->site->id}/{$this->environment->id}/{$this->get('filename')}",
                    'bucket' => $this->getBucket(),
                ],
            ]
        );
        return $workflow;
    }

    /**
     * Formats the object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return [
            'file'      => $this->get('filename'),
            'size'      => $this->getSizeInMb(),
            'date'      => $this->getDate(),
            'initiator' => $this->getInitiator(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function parseAttributes($data)
    {
        list($data->scheduled_for, $data->archive_type, $data->type) = explode('_', $data->id);
        return $data;
    }
}
