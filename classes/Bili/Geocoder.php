<?php

namespace Bili;

class Geocoder
{
    protected static $googleMapsApi = "http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false";

    public static function addressToLatLng($strAddress)
    {
        if (empty($strAddress)) {
            throw new \InvalidArgumentException("Invalid address supplied in Geocoder::addressToLatLng()", E_ERROR);
        }

        $objCurlRequest = curl_init();
        curl_setopt($objCurlRequest, CURLOPT_URL, self::$googleMapsApi);
        curl_setopt($objCurlRequest, CURLOPT_RETURNTRANSFER, 1);

        $arrResponse = json_decode(
            curl_exec($objCurlRequest),
            true
        );

        if ($arrResponse['status'] !== 'OK') {
            throw new \RuntimeException("No valid response received from Google Maps API.", E_ERROR);
        }

        $arrGeometry = $arrResponse['results'][0]['geometry'];

        return array(
        	'latitude' => $arrGeometry['location']['lat'],
        	'longitude' => $arrGeometry['location']['lng'],
        	'locationType' => $arrGeometry['location']['location_type']
        );
    }
}
