<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Session\Session;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Finder\Finder;
use Terminus\Exceptions\TerminusException;

/**
 * Class SavedTokens
 * @package Pantheon\Terminus\Collections
 */
class SavedTokens extends TerminusCollection implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @var Session
     */
    public $session;
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\SavedToken';

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->session = $options['session'];
    }

    /**
     * Saves a machine token to the tokens directory and logs the user in
     *
     * @param string $token The machine token to be saved
     */
    public function create($token_string)
    {
        $token =  $this->getContainer()->get(
            SavedToken::class,
            [(object)['token' => $token_string,], ['collection' => $this,]]
        );
        $user = $token->logIn();
        $user->fetch();
        $token->id = $user->get('email');
        $token->set('email', $user->get('email'));
        $token->saveToDir();
        $this->models[$token->id] = $token;
    }

    /**
     * Retrieves the model with site of the given email or machine token
     *
     * @param string $id Email or machine token to look up a saved token by
     * @return \Pantheon\Terminus\Models\SavedToken
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function get($id)
    {
        $tokens = $this->getMembers();
        if (isset($tokens[$id])) {
            return $tokens[$id];
        } else {
            foreach ($tokens as $token) {
                if ($id == $token->get('token')) {
                    return $token;
                }
            }
        }
        throw new TerminusException('Could not find a saved token identified by {id}.', compact('id'));
    }

    /**
     * @inheritdoc
     */
    protected function getCollectionData($options = [])
    {
        $finder = new Finder();
        $iterator = $finder->files()->in($this->getConfig()->get('tokens_dir'));
        $tokens = [];
        foreach ($iterator as $file) {
            $tokens[] = json_decode(file_get_contents($file->getRealPath()));
        }
        return $tokens;
    }
}
