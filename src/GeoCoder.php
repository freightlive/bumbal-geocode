<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\GeoCoderOptions;


class GeoCoder {

    protected $strategies;

    /**
     * GeoCoder constructor.
     * @param GeoProviderStrategyList $strategies
     */
    public function __construct(GeoProviderStrategyList $strategies){
        $this->strategies = $strategies;
    }

    /**
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy){
        $result = $this->strategies->getLatLngResultListForAddress($address, $accuracy);

        return $result;
    }
}
