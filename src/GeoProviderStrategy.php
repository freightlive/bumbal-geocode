<?php


namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderStrategyOptions;

abstract class GeoProviderStrategy {

    protected $providers;
    protected $options;

    public function __construct(GeoProviderList $providers, GeoProviderStrategyOptions $options) {
        $this->providers = $providers;
        $this->options = $options;
    }

    /**
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy){
        $result = new LatLngResultList();
        foreach($this->providers as $provider){
            //@todo options, accuracy
            $provider_result = $provider->getLatLngResultListFromAddress($address, $accuracy, $this->options);
            $result->merge($provider_result);
            if ($this->options->quit_on_error && $provider_result->hasErrors()) {
                return $result;
            }

            if ($this->options->quit_after_first_result && count($provider_result) > 0) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * return if this concrete provider should be used for geocoding the given address
     * @param Address $address
     * @return boolean
     */
    abstract public function useForAddress(Address $address);
}