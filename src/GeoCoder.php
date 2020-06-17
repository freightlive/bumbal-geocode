<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\GeoCoderOptions;
use BumbalGeocode\GeoProviderStrategyList;

class GeoCoder {

    protected $strategies;

    protected $options;

    /**
     * GeoCoder constructor.
     * @param GeoProviderStrategyList $strategies
     * @param GeoCoderOptions $options
     */
    public function __construct(GeoProviderStrategyList $strategies, GeoCoderOptions $options = NULL){
        $this->strategies = $strategies;
        $this->options = ($options ? $options : new GeoCoderOptions());
    }

    /**
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, /*float*/ $accuracy){
        $result = new LatLngResultList();
        foreach($this->strategies as $strategy){
            if($strategy->useForAddress($address)) {
                $result = $strategy->getLatLngResultListForAddress($address, $accuracy);
                if($result->hasResults()) {
                    break;
                }
            }
        }

        return $result;
    }
}
