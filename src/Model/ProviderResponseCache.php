<?php


namespace BumbalGeocode\Model;


class ProviderResponseCache {
    private $url_to_response = [];

    /**
     * @param string $url
     * @param array $result
     */
    public function setProviderResponse(/*string*/ $url, /*array*/ $result) {
        $this->url_to_response[$url] = $result;
    }

    /**
     * @param string $url
     * @return array
     */
    public function getProviderResponse(/*string*/ $url) {
        if($this->hasProviderResponse($url)) {
            return $this->url_to_response[$url];
        }
        return [];
    }

    /**
     * @param string $url
     * @return bool
     */
    public function hasProviderResponse(/*string*/ $url) {
        return !empty($this->url_to_response[$url]);
    }
}