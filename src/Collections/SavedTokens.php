<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SavedToken;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class SavedTokens
 * @package Pantheon\Terminus\Collections
 */
class SavedTokens extends TerminusCollection implements ConfigAwareInterface, DataStoreAwareInterface
{
    use ConfigAwareTrait;
    use DataStoreAwareTrait;

    public const PRETTY_NAME = 'tokens';
    /**
     * @var string
     */
    protected $collected_class = SavedToken::class;

    /**
     * Adds a model to this collection
     *
     * @param object $model_data Data to feed into attributes of new model
     * @param array $options Data to make properties of the new model
     * @return TerminusModel
     */
    public function add($model_data, array $options = [])
    {
        $model = parent::add($model_data, $options);
        $model->setDataStore($this->getDataStore());
        return $model;
    }

    /**
     * Saves a machine token to the tokens directory and logs the user in
     *
     * @param string $token_string The machine token to be saved
     * @param callable|null $confirm_overwrite Called with the account email when a saved token for that account
     *   already exists and would be replaced; must return true to proceed.
     * @throws TerminusException If an existing saved token would be overwritten without confirmation
     */
    public function create($token_string, ?callable $confirm_overwrite = null)
    {
        $token_nickname = "token-" . \uniqid();
        $this->getContainer()->add($token_nickname, SavedToken::class)
            ->addArguments([
                (object)['token' => $token_string],
                ['collection' => $this]
            ]);
        $token =  $this->getContainer()->get($token_nickname);
        $token->setDataStore($this->getDataStore());
        $user = $token->logIn();
        $user->fetch();
        $user_email = $user->get('email');
        if (
            $confirm_overwrite !== null
            && $this->getDataStore()->has($user_email)
            && !$confirm_overwrite($user_email)
        ) {
            throw new TerminusException(
                'A machine token is already saved for {email} and was not overwritten. Re-run with --yes to'
                . ' replace it. You are logged in for this session only.',
                ['email' => $user_email]
            );
        }
        $token->id = $user_email;
        $token->set('email', $user_email);
        $token->saveToDir();
        $this->models[$token->id] = $token;
    }

    /**
     * Delete all of the saved tokens.
     */
    public function deleteAll()
    {
        foreach ($this->all() as $token) {
            $token->delete();
        }
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if (!empty(parent::getData())) {
            return parent::getData();
        }

        $keys = array_filter(
            $this->getDataStore()->keys(),
            fn ($keys) => preg_match('/\S+@\S+\.\S+/', $keys)
        );
        $tokens = array_filter(array_map(
            fn ($key) => $this->getDataStore()->get($key),
            $keys
        ));
        $this->setData($tokens);

        return $tokens;
    }
}
