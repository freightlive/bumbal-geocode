<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;

interface GeoProvider {

    /**
     * @param Address $address
     * @param float $precision
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, float $precision);
}