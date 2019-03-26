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
                switch($key){
                    case 'zipcode':
                    case 'iso_country':
                        $this->$key = $this->normalize($key, trim($value));
                    default:
                        $this->$key = trim($value);
                }
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
        $this->street = trim($street);
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
    public function setHouseNr(string $house_nr){
        $this->house_nr = trim($house_nr);
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
        $this->city = trim($city);
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
        $this->iso_country = $this->normalize('iso_country', trim($iso_country));
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
        $this->zipcode = $this->normalize('zipcode', trim($zipcode));
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
            throw new \Exception('Missing fields in Address: \'' . implode('\', \'', $missing_fields).'\'');
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
     * 1 -> certainly the same address
     * 0 -> certainly not the same address
     * @param Address $address
     * @return float
     */
    public function compare(Address $address){
        mb_internal_encoding('UTF-8');

        //if country iso code doesn't match, it's a big fail
        if(strtolower($address->getIsoCountry()) != strtolower($this->iso_country)){
            return 0.0;
        }

        $this_array = array_filter($this->toArray());
        array_walk($this_array, function(&$value, $key, $normalize_callback){
            $value = $normalize_callback($key, $value);
        }, [$this, 'normalize']);

        $address_array = array_filter($address->toArray());
        array_walk($address_array, function(&$value, $key, $normalize_callback){
            $value = $normalize_callback($key, $value);
        }, [$this, 'normalize']);

        $elements_in_both = array_keys(array_intersect_key($this_array, $address_array));

        $count_equal = 0;
        $elements_no_match = [];
        foreach ($elements_in_both as $key) {
            if($this_array[$key] == $address_array[$key]){
                $count_equal++;
            } else {
                $elements_no_match[] = $key;
            }
        }

        return $count_equal/count($this_array);


        var_dump($elements_in_both);
        //$equals = array_walk($intersect_array)

        die();
        $this_city_normalized = trim(mb_strtolower($this->city));
        $address_city_normalized = trim(mb_strtolower($address->getCity()));
        $city_similarity = 0.0;
        similar_text($this_city_normalized, $address_city_normalized, $city_similarity);
        $city_similarity /= 100;

        $this_street_normalized = trim(mb_strtolower($this->street));
        $address_street_normalized = trim(mb_strtolower($address->getStreet()));
        $street_similarity = 0.0;
        similar_text($this_street_normalized, $address_street_normalized, $street_similarity);
        $street_similarity /= 100;

        $this_house_nr_normalized = str_replace([' ','-','/'],'', mb_strtolower($this->house_nr));
        $address_house_nr_normalized = str_replace([' ','-','/'],'', mb_strtolower($address->getHouseNr()));

        if(empty($this->house_nr)){
            //closest we can match is zipcode or street/city
            if(empty($this->zipcode) && empty($this->street)){
                //closest we can match is city
                return $city_similarity;
            } elseif(empty($this->zipcode)){
                //closest we can match is street/city
                return $street_similarity * $city_similarity;
            } else {
                //closest we can match is zipcode (city is contained in zipcode)
                return ($this->zipcode == $address->getZipcode() ? 1.0 : 0.0);
            }
        } else {
            //house_nr is set. Closest we can match is zipcode/house_nr or street/house_nr/city
            if(empty($this->zipcode) && empty($this->street)){
                //closest we can match is city, house_nr is irrelevant without street or zipcode
                return $city_similarity;
            } elseif(empty($this->zipcode)){
                //closest we can match is street/house_nr/city
                return $street_similarity * $city_similarity * ($this_house_nr_normalized == $address_house_nr_normalized ? 1.0 : 0.0);
            } else {
                return ($this->zipcode == $address->getZipcode() ? 1.0 : 0.0) * ($this_house_nr_normalized == $address_house_nr_normalized ? 1.0 : 0.0);
            }
        }
    }

    /**
     * @param Address $address
     * @return float|int
     */
    public function similarity(Address $address){
        mb_internal_encoding('UTF-8');
        $result = 0.0;
        try {
            $address_string = mb_strtolower($address->getAddressString());
            $address_string_this = mb_strtolower($this->getAddressString());

        } catch (\Exception $e){
            return 0.0;
        }
        similar_text($address_string_this, $address_string, $result);
        return $result/100;
    }


    private function normalize($key, $value){
        mb_internal_encoding('UTF-8');

        switch($key){
            case 'house_nr':
                $result = str_replace([' ','-','/'],'', mb_strtolower($value));
                break;
            case 'zipcode':
                $result = str_replace(' ','', mb_strtoupper($value));
                break;
            case 'city':
                $result = str_replace('-',' ', mb_strtolower($value));
                break;
            case 'iso_country':
                $result = mb_strtoupper($value);
                break;
            default:
                $result = mb_strtolower($value);
        }

        return $result;
    }

}