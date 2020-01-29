<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

/**
 * Class Plan
 * @package Pantheon\Terminus\Models
 */
class Plan extends TerminusModel implements SiteInterface
{
    use SiteTrait;

    const PRETTY_NAME = 'plan';
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/plan';

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        if (($attributes !== null) && property_exists($attributes, 'attributes')) {
            $attributes = (object)$attributes->attributes;
        }
        parent::__construct($attributes, $options);
    }

    /**
     * @return float|int
     */
    public function getMonthlyPrice()
    {
        $price = (integer)$this->get('price');
        if ($this->isAnnual()) {
            return $price/12;
        }
        return $price;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = $this->get('name');
        return !is_null($name) ? $name : $this->get('plan_name');
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->getSku(),];
    }

    /**
     * @return string
     */
    public function getSku()
    {
        $sku = $this->get('sku');
        return !is_null($sku) ? $sku : $this->get('plan_sku');
    }

    /**
     * @return boolean
     */
    public function isAnnual()
    {
        return $this->get('billing_cycle') === 'annual';
    }

    /**
     * @return boolean
     */
    public function isFree()
    {
        return strpos($this->getSku(), 'plan-free') === 0;
    }

    /**
     * @return boolean
     */
    public function isMonthly()
    {
        return $this->get('billing_cycle') === 'monthly';
    }

    /**
     * @return null|string
     */
    public function formatPrice($price)
    {
        if (!$this->isFree() && ($price === 0)) {
            return null;
        }
        return sprintf($this->getConfig()->get('monetary_format'), ($price / 100));
    }


    /**
     * Formats plan object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return [
            'billing_cycle' => $this->get('billing_cycle'),
            'id' => $this->id,
            'monthly_price' => $this->formatPrice($this->getMonthlyPrice()),
            'name' => $this->getName(),
            'price' => $this->formatPrice($this->get('price')),
            'sku' => $this->getSku(),
        ];
    }
}
