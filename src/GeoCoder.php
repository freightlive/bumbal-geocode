<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\GeoCoderOptions;


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
    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy){
        $result = $this->strategies->getLatLngResultListForAddress($address, $accuracy);

        if($this->options->diagnose) {

        }
        return $result;
    }
}
