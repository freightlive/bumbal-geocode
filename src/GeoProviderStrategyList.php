<?php


namespace BumbalGeocode;

use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Util\PriorityList;

class GeoProviderStrategyList extends PriorityList {
    /**
     * priority 0 is highest.
     * @param GeoProviderStrategy $strategy
     * @param int $priority
     */
    public function setProviderStrategy(GeoProviderStrategy $strategy, /*int*/ $priority = 0){
        $this->setItem($strategy, $priority);
    }

    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy){
        $result = new LatLngResultList();
        foreach($this->getItems() as $strategy){
            /** @var LatLngResultList $strategy_result **/
            $strategy_result = $strategy->getLatLngResultListForAddress($address, $accuracy);
            $result->merge($strategy_result);

            if($result->hasResults()) {
                break;
            }

        }
        return $result;
    }

}