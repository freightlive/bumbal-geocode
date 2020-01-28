<?php


namespace BumbalGeocode\Providers\Google;

class GoogleRequest {

    const GOOGLE_STATUS_ACCEPTED = [
        'ZERO_RESULTS',
        'OK'
    ];

    private $api_key;

    /**
     * GoogleGeoProvider constructor.
     * @param string $api_key
     */
    public function __construct(/*string*/ $api_key) {
        $this->api_key = $api_key;
    }

    /**
     * @param string $url
     * @return array mixed
     * @throws \Exception
     */
    private function request(/*string*/ $url) {

        $channel = curl_init();

        curl_setopt($channel,CURLOPT_URL, $url);
        curl_setopt($channel,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel,CURLOPT_HEADER, false);
        curl_setopt($channel,CURLOPT_POST, false);
        curl_setopt($channel,CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($channel,CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($channel);

        if (curl_error($channel)) {
            throw new \Exception('Google maps API request failed. Curl returned error ' . curl_error($channel));
        }

        $response_data = json_decode($response, true);
        $this->validateResult($response_data);

        return $response_data;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function validateResult(/*array*/ $data){
        if(empty($data['status']) || !in_array($data['status'], self::GOOGLE_STATUS_ACCEPTED)){
            throw new \Exception('Google maps API returned Status Code: '.$data['status']);
        }

        if(!empty($data['error_message'])){
            throw new \Exception('Google maps API returned Error Message: '.$data['error_message']);
        }
        return true;
    }
}