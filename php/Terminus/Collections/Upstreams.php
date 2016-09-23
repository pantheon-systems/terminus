<?php

namespace Terminus\Collections;

use Terminus\Session;

class Upstreams extends TerminusCollection
{
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\Upstream';
  /**
   * @var string
   */
    protected $url = 'products';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->user = Session::getUser();
    }

  /**
   * Search available upstreams by UUID or name
   *
   * @param string $id_or_name UUID or name
   * @return Upstream
   */
    public function getByIdOrName($id_or_name)
    {
        $members   = $this->getMemberList('id', 'longname');
        $member_id = null;
        if (isset($members[$id_or_name])) {
            $member_id = $id_or_name;
        } else {
            $member_id = array_search($id_or_name, $members);
        }
        $member = $this->get($member_id);
        return $member;
    }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data Data to feed into attributes of new model
   * @param array  $options    Data to make properties of the new model
   * @return void
   */
    public function add($model_data, array $options = [])
    {
        parent::add($model_data->attributes, $options);
    }
}
