<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
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

    const PRETTY_NAME = 'tokens';
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
     * @param string $token The machine token to be saved
     */
    public function create($token_string)
    {
        $token = new SavedToken(
            (object)['token' => $token_string],
            ['collection' => $this]
        );
        $this->getContainer()->inflect($token);
        $token->setDataStore($this->getDataStore());
        $user = $token->logIn();
        $user->fetch();
        $user_email = $user->get('email');
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
        if (empty(parent::getData())) {
            $keys = $this->getDataStore()->keys();

            $tokens = [];
            foreach ($keys as $key) {
                $tokens[] = $this->getDataStore()->get($key);
            }
            $this->setData($tokens);
        }
        return parent::getData();
    }
}
