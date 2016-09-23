<?php

namespace Terminus\Commands;

use Terminus\Session;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\User;

/**
 * Show information for your Pantheon instruments
 *
 * @command instruments
 */
class InstrumentsCommand extends TerminusCommand
{

  /**
   * Instantiates object, ensures login
   *
   * @param array $options Options to construct the command object
   * @returns InstrumentsCommand
   */
    public function __construct(array $options = [])
    {
        $options['require_login'] = true;
        parent::__construct($options);
    }

  /**
   * Show a list of your instruments on Pantheon
   *
   * @subcommand list
   */
    public function all($args, $assoc_args)
    {
        $user        = Session::getUser();
        $instruments = $user->instruments->all();
        $data        = array();
        foreach ($instruments as $id => $instrument) {
            $data[] = array(
            'label' => $instrument->get('label'),
            'id'    => $instrument->id,
            );
        }
        if (empty($data)) {
            $this->log()->info('There are no instruments attached to this account.');
        }
        $this->output()->outputRecordList($data);
    }
}
