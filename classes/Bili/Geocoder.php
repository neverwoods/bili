<?php

namespace Bili;

class Geocoder
{
    protected static $googleMapsApi = "http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false";

    public static function addressToLatLng($strAddress)
    {
        if (empty($strAddress)) {
            throw new \InvalidArgumentException("Empty address supplied in Geocoder::addressToLatLng(). Cannot proceed.", E_ERROR);
        }

        $objCurlRequest = curl_init();
        curl_setopt($objCurlRequest, CURLOPT_URL, sprintf(self::$googleMapsApi, $strAddress));
        curl_setopt($objCurlRequest, CURLOPT_RETURNTRANSFER, 1);

        $arrResponse = json_decode(
            curl_exec($objCurlRequest),
            true
        );

        if (is_null($arrResponse) 
            || !isset($arrResponse['status']) 
            || $arrResponse['status'] !== 'OK'
        ) {
            throw new \RuntimeException("No valid response received from Google Maps API.", E_ERROR);
        }

        // Fetch geometry data from response
        if (!isset($arrResponse['results']) 
            || !is_array($arrResponse['results'][0]) 
            || !isset($arrResponse['results'][0]['geometry'])
            || !isset($arrResponse['results'][0]['geometry']['location'])
            || !isset($arrResponse['results'][0]['geometry']['location']['lat'])
            || !isset($arrResponse['results'][0]['geometry']['location']['lng'])
        ) {
            throw new \UnexpectedValueException("Could not read geometry data from Google Maps API response data.", E_ERROR);
        }

        $arrGeometry = $arrResponse['results'][0]['geometry'];
        return array(
        	'latitude' => $arrGeometry['location']['lat'],
        	'longitude' => $arrGeometry['location']['lng']
        );
    }
}
