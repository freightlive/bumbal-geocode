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

    public function getLatLngResultListForAddress(Address $address, /*float*/ $accuracy, /*bool*/ $diagnose = false){
        $result = new LatLngResultList();
        foreach($this->getItems() as $strategy){
            if($strategy->useForAddress($address)) {
                $result = $strategy->getLatLngResultListForAddress($address, $accuracy);

                if($diagnose) {

                }

                if($result->hasResults()) {
                    break;
                }
            }
        }
        return $result;
    }

}