<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;

interface GeoProvider {

    /**
     * @param Address $address
     * @param float $min_accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListForAddress(Address $address, /*float*/ $min_accuracy);
}
