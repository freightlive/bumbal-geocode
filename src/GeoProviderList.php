<?php

namespace BumbalGeocode;

use BumbalGeocode\Util\PriorityList;

class GeoProviderList extends PriorityList {

    /**
     * priority 0 is highest.
     * @param GeoProvider $provider
     * @param int $priority
     */
    public function setProvider(GeoProvider $provider, /*int*/ $priority = 0){
        $this->setItem($provider, $priority);
    }

    /**
     * @param int $index
     * @return GeoProvider|null
     */
    public function getProvider(/*int*/ $index){
        return $this->getItem($index);
    }

    /**
     * @param int|NULL $priority
     * @return array|mixed
     */
    public function getProviders(/*int*/ $priority = NULL){
        return $this->getItems();
    }
}
