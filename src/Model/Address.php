<?php

namespace BumbalGeocode\Model;

class Address {

    /**
     * @var string
     */
    protected $street;

    /**
     * @var string
     */
    protected $house_nr;

    /**
     * @var string
     */
    protected $zipcode;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $iso_country;

    /**
     * Address constructor.
     * @param array $data
     */
    public function __construct(array $data = []){
        foreach($data as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    public function toArray(){
        return get_object_vars($this);
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


    /**
     * @param Address $address
     * @return string
     * @throws \Exception
     */
    public function getAddressString(){

        $address_data = $this->toArray();

        $minimum_needed_fields = [
            ['iso_country'],
            ['city']
        ];

        $filtered_address_data = array_filter($address_data);

        $missing_fields = [];
        foreach($minimum_needed_fields as $fields) {
            if(!array_intersect($fields, array_keys($filtered_address_data))) {
                $missing_fields[] = implode(' or ', $fields);
            }
        }

        if(!empty($missing_fields)) {
            throw new \Exception('Missing fields in Address ' . implode(', ', $missing_fields));
        }

        $address_array = [
            [
                empty($address_data['street'])?'':$address_data['street'],
                empty($address_data['house_nr'])?'':$address_data['house_nr']
            ],
            [
                empty($address_data['zipcode'])?'':$address_data['zipcode'],
                empty($address_data['city'])?'':$address_data['city'],
                empty($address_data['iso_country'])?'':$address_data['iso_country']
            ],
        ];

        foreach($address_array as $key => $value) {
            $value = array_filter($value);
            $address_array[$key] = implode(' ',$value);
        }

        $address_array = array_filter($address_array);
        return implode(', ', $address_array);
    }

    /**
     * return value from 0 to 1
     * @param Address $address
     * @return float
     */
    public function compare(Address $address){
        mb_internal_encoding('UTF-8');

        //country and city have to match
        if(strtolower($address->getIsoCountry()) != strtolower($this->iso_country)){
            return 0.0;
        }

        if(mb_strtolower($address->getCity()) != mb_strtolower($this->city)){
            return 0.0;
        }

        //give points for each matching member, adding up to 1
        $result = 0.0;
        if(str_replace(' ','', mb_strtolower($address->getZipcode())) == str_replace(' ','', mb_strtolower($this->zipcode))){
            $result += 0.2;
        }

        if(mb_strtolower($address->getStreet()) == mb_strtolower($this->street)){
            $result += 0.6;
        }

        if(str_replace([' ','-'],'',mb_strtolower($address->getHouseNr())) == str_replace([' ','-'],'',mb_strtolower($this->house_nr))){
            $result += 0.2;
        }

        return $result;
    }

    /**
     * @param Address $address
     * @return float|int
     */
    public function similarity(Address $address){
        $result = 0.0;
        try {
            $address_string = $address->getAddressString();
            $address_string_this = $this->getAddressString();

        } catch (\Exception $e){
            return 0.0;
        }
        similar_text($address_string_this, $address_string, $result);
        return $result/100;
    }
}