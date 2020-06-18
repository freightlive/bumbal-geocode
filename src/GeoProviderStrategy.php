<?php


namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderStrategyOptions;

class GeoProviderStrategy {

    protected $providers;
    protected $options;
    protected $condition;

    public function __construct(GeoProviderList $providers, callable $condition, GeoProviderStrategyOptions $options) {
        $this->providers = $providers;
        $this->options = $options;
        $this->condition = $condition;
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
    public function useForAddress(Address $address) {
        if($this->condition) {
            return call_user_func($this->condition, $address);
        }
        return true;
    }
}