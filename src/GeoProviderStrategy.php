<?php


namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderStrategyOptions;

class GeoProviderStrategy {

    protected $providers;
    protected $options;
    protected $condition;
    protected $id;

    /**
     * GeoProviderStrategy constructor.
     * @param string $id
     * @param GeoProviderList $providers
     * @param GeoProviderStrategyOptions $options
     * @param callable|null $condition
     */
    public function __construct(/*string*/ $id, GeoProviderList $providers, GeoProviderStrategyOptions $options, callable $condition = null) {
        $this->providers = $providers;
        $this->options = $options;
        $this->condition = $condition;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy){
        $result = new LatLngResultList();

        $address_as_string = '';
        try {
            $address_as_string = $address->getAddressString();
        } catch(\Exception $e) {
            $result->setLogMessage('Strategy \''.$this->id. '\' failed: '.$e->getMessage());
            return $result;
        }

        if(!$this->useForAddress($address)) {
            $result->setLogMessage('Strategy \''.$this->id. '\' rejected address '.$address_as_string.' based on condition');
            return $result;
        }
        $result->setLogMessage('Strategy \''.$this->id. '\' accepted address '.$address_as_string);

        foreach($this->providers as $provider){
            /**
             * @var LatLngResultList $provider_result
             */
            $provider_result = $provider->getLatLngResultListForAddress($address, $accuracy);
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