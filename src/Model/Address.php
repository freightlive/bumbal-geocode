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
    public function __construct(/*array*/ $data = []){
        foreach($data as $key => $value){
            if(property_exists($this, $key)){
                switch($key){
                    case 'zipcode':
                    case 'iso_country':
                        $this->$key = $this->normalize($key, trim($value));
                        break;
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
    public function setStreet(/*string*/ $street){
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
    public function setHouseNr(/*string*/ $house_nr){
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
    public function setCity(/*string*/ $city){
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
    public function setIsoCountry(/*string*/ $iso_country){
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
    public function setZipcode(/*string*/ $zipcode){
        $this->zipcode = $this->normalize('zipcode', trim($zipcode));
    }


    /**
     * @param bool $exclude_iso_country
     * @return string
     * @throws \Exception
     */
    public function getAddressString($exclude_iso_country = false){

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

        //ignore house_nr without street or zipcode
        if(!empty($address_data['house_nr']) && empty($address_data['zipcode']) && empty($address_data['street'])){
            unset($address_data['house_nr']);
        }

        $address_array = [
            [
                empty($address_data['street'])?'':$address_data['street'],
                empty($address_data['house_nr'])?'':$address_data['house_nr']
            ],
            [
                empty($address_data['zipcode'])?'':$address_data['zipcode'],
                empty($address_data['city'])?'':$address_data['city'],
                (empty($address_data['iso_country']) || $exclude_iso_country)?'':$address_data['iso_country']
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
     * @param array $ignore_fields
     * @return float
     */
    public function compare(Address $address, $ignore_fields = []){
        mb_internal_encoding('UTF-8');

        //if country iso code doesn't match, it's a big fail
        if(strtolower($address->getIsoCountry()) != strtolower($this->iso_country)){
            return 0.0;
        }

        $this_array = array_filter($this->toArray());
        //we've already matched country, so unset
        unset($this_array['iso_country']);

        //ignore house_nr without street or zipcode
        if(!empty($this_array['house_nr']) && empty($this_array['zipcode']) && empty($this_array['street'])){
            unset($this_array['house_nr']);
        }

        //normalize values, so we can compare more accurately
        array_walk($this_array, function(&$value, $key, $normalize_callback){
            $value = $normalize_callback($key, $value);
        }, [$this, 'normalize']);

        $address_array = array_filter($address->toArray());
        //we've already matched country, so unset
        unset($address_array['iso_country']);

        //normalize values, so we can compare more accurately
        array_walk($address_array, function(&$value, $key, $normalize_callback){
            $value = $normalize_callback($key, $value);
        }, [$this, 'normalize']);

        array_walk($ignore_fields, function(&$value, $key) use(&$address_array, &$this_array){
            unset($address_array[$value]);
            unset($this_array[$value]);
        });

        $elements_in_both = array_keys(array_intersect_key($this_array, $address_array));

        //need to know how many extra elements $this has compared to $address.
        $elements_only_in_this = array_keys(array_diff_key($this_array, $address_array));

        //need to know how many extra elements $address has compared to $this. More bloat in the $address means a worse result.
        $elements_only_in_address = array_keys(array_diff_key($address_array, $this_array));

        //check how many elements match
        $elements_no_match = [];
        $elements_match = [];
        foreach ($elements_in_both as $key) {
            if($this_array[$key] != $address_array[$key]){
                $elements_no_match[] = $key;
            } else {
                $elements_match[] = $key;
            }
        }

        //if house_nr was not set, it could be possible that house_nr is contained in street
        if(in_array('house_nr', $elements_only_in_address) && in_array('street', $elements_no_match)){
            //is house_nr exactly contained in street, and at the end?
            if(mb_strrpos($this_array['street'], $address_array['house_nr']) === mb_strlen($this_array['street']) - mb_strlen($address_array['house_nr'])){
                //split $this_array['street'] and do bookkeeping
                $this_array['street'] = trim(str_replace($address_array['house_nr'], '', $this_array['street']));
                $this_array['house_nr'] = $address_array['house_nr'];
                $elements_only_in_address = array_diff($elements_only_in_address, ['house_nr']);
                $elements_match[] = 'house_nr';
            } elseif(mb_strpos($this_array['street'], $address_array['street']) === 0) {
                //street is exactly contained at the start, change $address_array['house_nr'] from $elements_only_in_address to the end of $address_array['street']
                //and do bookkeeping
                $elements_only_in_address = array_diff($elements_only_in_address, ['house_nr']);
                $address_array['street'] = $address_array['street'].' '.$address_array['house_nr'];
            } else {
                //street and house_nr are both not contained. Don't mess with existing street data, but introduce new element to text compare
                $elements_only_in_address = array_diff($elements_only_in_address, ['house_nr']);
                $address_array['street-alt'] = $address_array['street'].' '.$address_array['house_nr'];
                $this_array['street-alt'] = $this_array['street'];
                $elements_no_match[] = 'street-alt';
            }

            //exact match for street now?
            if($address_array['street'] == $this_array['street']){
                //do bookkeeping
                $elements_no_match = array_diff($elements_no_match, ['street']);
                $elements_match[] = 'street';
            }
            //else let $elements_no_match street compare handle it to score text similarity
        }

        //if zipcode and house_nr match, street is perfectly fine to have in address
        if(in_array('house_nr', $elements_match) && in_array('zipcode', $elements_match)){
            $elements_only_in_address = array_diff($elements_only_in_address, ['street']);
        }

        //if street is a match, zipcode is perfectly fine to have in address
        if(in_array('street', $elements_match)){
            $elements_only_in_address = array_diff($elements_only_in_address, ['zipcode']);
        }

        if(empty($elements_no_match) && empty($elements_only_in_address) && empty($elements_only_in_this)){
            //exact match
            return 1.0;
        }

        //matching elements score 1.0
        $results = array_fill(0 , count($elements_match), 1.0 );
        //elements only in $address
        $results = array_merge($results, array_fill(0 , count($elements_only_in_address), 0.5 ));
        //elements only in $this
        $results = array_merge($results, array_fill(0 , count($elements_only_in_this), 0.3 ));

        //score elements that weren't an exact match
        foreach($elements_no_match as $key){
            switch($key){
                case 'zipcode':
                    //check how many characters of zipcode do match from the beginning
                    $count = 0;
                    foreach (str_split($this_array['zipcode']) as $character){
                        if(empty($address_array['zipcode'][$count]) || $character != $address_array['zipcode'][$count]){
                            break;
                        }
                        $count++;
                    }

                    $results[] = $count/strlen($this_array['zipcode']);
                    break;
                case 'city':
                    $city_result = 0.0;
                    //check if city is contained in result city (Gilze-Rijen, Son en Breugel)
                    $city_parts = preg_split("/( |-)/", $address_array['city']);
                    if(in_array($this_array['city'], $city_parts)){
                        $city_result = 0.9;
                    }

                    //check string similarity
                    $city_result = max($city_result, $this->stringSimilarity($this_array['city'], $address_array['city']));

                    //city sometimes doesn't match, while rest of address is perfect. In that case, subtract less for not matching city
                    if(empty(array_diff(['zipcode', 'house_nr', 'street'], $elements_match))){
                       $city_result = max($city_result, 0.6);
                    }

                    $results[] = $city_result;
                    break;
                case 'street':
                    //check street similarity
                    $results[] = $this->stringSimilarity($this_array['street'], $address_array['street']);
                    break;
                case 'house_nr':
                    //check how many characters of house_nr do match from the beginning
                    $count = 0;
                    foreach (str_split($this_array['house_nr']) as $character){
                        if(empty($address_array['house_nr'][$count]) || $character != $address_array['house_nr'][$count]){
                            break;
                        }
                        $count++;
                    }

                    $results[] = $count/max(strlen($this_array['house_nr']),strlen($address_array['house_nr']));
                    break;
                default:
                    $results[] = $this->stringSimilarity($this_array[$key], $address_array[$key]);
            }
        }
        /*var_dump($results);
        var_dump($elements_only_in_address);
        var_dump($elements_only_in_this);
        var_dump($elements_no_match);
        echo $address->getAddressString()."\n";*/
        return array_sum($results)/count($results);
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

    private function stringSimilarity(/*string*/ $a, /*string*/ $b){
        $result = 0.0;
        similar_text($a, $b, $result);
        return $result / 100.0;
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
