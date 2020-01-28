<?php


namespace BumbalGeocode;


use BumbalGeocode\Model\AddressResultList;

interface GeoReverseProvider {

    /**
     * @param $latitude
     * @param $longitude
     * @return AddressResultList
     */
    public function getAddressResultListFromLatLng(/*float*/ $latitude, /*float*/ $longitude);
}