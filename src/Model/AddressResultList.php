<?php


namespace BumbalGeocode\Model;


class AddressResultList extends ResultList {
    /**
     * AddressResultList constructor.
     * @param array $addresses
     */
    public function __construct(/*array*/ $addresses = []) {
        $this->items = $addresses;
    }

    /**
     * Merges a AddressResultList into this one.
     * @param AddressResultList $address_result_list
     */
    public function merge(AddressResultList $address_result_list){
        $this->items = array_merge($this->items, $address_result_list->getAddresses());
        $this->log = array_merge($this->log, $address_result_list->getLog());
        $this->errors = array_merge($this->errors, $address_result_list->getErrors());
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address){
        $this->items[] = $address;
    }

    /**
     * @param array $addresses
     */
    public function setAddresses(/*array*/ $addresses){
        $this->items = $addresses;
    }

    /**
     * @return array
     */
    public function getAddresses(){
        return $this->items;
    }
}