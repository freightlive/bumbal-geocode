<?php


namespace BumbalGeocode;

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

    /**
     * @param int $index
     * @return GeoProviderStrategy|null
     */
    public function getProviderStrategy(/*int*/ $index){
        return $this->getItem($index);
    }

    /**
     * @param int|NULL $priority
     * @return array|mixed
     */
    public function getGeoProviderStrategies(/*int*/ $priority = NULL){
        return $this->getItems();
    }
}