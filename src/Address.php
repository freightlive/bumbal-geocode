<?php

namespace BumbalGeocode;

class Address {

    public $street;
    public $house_nr;
    public $zipcode;
    public $city;
    public $iso_country;

    /**
     * Address constructor.
     * @param array $address_data
     */
    public function __construct(array $address_data = []){
        foreach($address_data as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street){
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getStreet(){
        return $this->street;
    }

    /**
     * @param string $house_nr
     */
    public function houseNr(string $house_nr){
        $this->house_nr = $house_nr;
    }

    /**
     * @return string
     */
    public function getHouseNr(){
        return $this->house_nr;
    }


    /**
     * @return string
     */
    public function getCity(){
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city){
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getIsoCountry(){
        return $this->iso_country;
    }

    /**
     * @param string $iso_country
     */
    public function setIsoCountry(string $iso_country){
        $this->iso_country = $iso_country;
    }

    /**
     * @return string
     */
    public function getZipcode(){
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode(string $zipcode){
        $this->zipcode = $zipcode;
    }
}