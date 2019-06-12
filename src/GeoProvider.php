<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;

interface GeoProvider {

    /**
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, /*float*/ $accuracy);

    /**
     * return if this concrete provider should be used for geocoding the given address
     * @param Address $address
     * @return boolean
     */
    public function useForAddress(Address $address);
}
