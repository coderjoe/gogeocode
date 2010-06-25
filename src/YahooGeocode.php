<?php
/* SVN FILE: $Id$ */
/**
 * GoGeocode object for use with the Yahoo Geocoding API
 *
 * Copyright (c) 2008.
 * Licensed under the MIT License.
 * See LICENSE for detailed information.
 * For credits and origins, see AUTHORS.
 *
 * PHP 5
 *
 * @filesource
 * @version             $Revision$
 * @modifiedby          $LastChangedBy$
 * @lastmodified        $Date$
 * @license             http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

require_once('BaseGeocode.php');

/**
 * Geocoder object for use with the Yahoo Geocoding API
 */
class YahooGeocode extends BaseGeocode
{
	/*
	 * Yahoo status codes grokked from:
	 * http://developer.yahoo.com/search/errors.html
	 */

	/**
	 * Status Code:
	 * HTTP Status 200 Success!
	 * @var int
	 * @access public
	 */
	const SUCCESS = 200;

	/**
	 * Status Code:
	 * HTTP Status 404 Not Found
	 * @var int
	 * @access public
	 */
	const NOT_FOUND = 404;


	/**
	 * Status Code:
	 * Bad request. The parameters passed to the service did not match as expected.
	 * The Message should tell you what was missing or incorrect.
	 * (Note: BaseGeocode does not return the error message)
	 * @var int
	 * @access public
	 */
	const BAD_REQUEST = 400;

	/**
	 * Status Code:
	 * Forbidden. You do not have permission to access this resource, or are over your rate limit.
	 * @var int
	 * @access public
	 */
	const BAD_KEY = 403;

	/**
	 * Status Code:
	 * Forbidden. You do not have permission to access this resource, or are over your rate limit.
	 * @var int
	 * @access public
	 */
	const TOO_MANY_QUERIES = 403;

	/**
	 * Status Code:
	 * Service unavailable. An internal problem prevented us from returning data to you.
	 * @var int
	 * @access public
	 */
	const SERVER_ERROR = 503;

	/**
	 * Geocode the given address. See BaseGeocode::geocode for detailed information
	 * about this function's return type.
	 *
	 * @param string $address The string address to retrieve geocode information about
	 * @return array An empty array on server not found. Otherwise an array of request and geocoded location information.
	 */
	public function geocode( $address )
	{
		$retVal = array();

		$urlBase = 'http://api.local.yahoo.com';
		$serviceName = '/MapsService';
		$version = '/V1';
		$method = '/geocode';

		$request = $urlBase;
		$request .= $serviceName;
		$request .= $version;
		$request .= $method . '?location=' . urlencode( $address ) . '&appid=' . $this->apiKey;

		$file = $this->loadXML( $request );

		if( empty( $file ) ) {
			return $retVal;
		}

		$retVal['Response'] = array(
			'Status' => $file['response'],
			'Request' => 'geocode'
		);

		if( $retVal['Response']['Status'] == YahooGeocode::SUCCESS ) {
			$xml = new SimpleXMLElement( $file['contents'] );

			$xml->registerXPathNamespace('urn','urn:yahoo:maps');

			$retVal['Placemarks'] = array();
			if( $xml ) {
				$results = $xml->xpath('//urn:Result');
				$countries = $xml->xpath('//urn:Country');
				$adminAreas = $xml->xpath('//urn:State');
				//Yahoo Geocoding has no Sub-Administrative Area (County) support.
				$localities = $xml->xpath('//urn:City');
				$thoroughfares = $xml->xpath('//urn:Address');
				$postalCodes = $xml->xpath('//urn:Zip');
				$latitudes = $xml->xpath('//urn:Latitude');
				$longitudes = $xml->xpath('//urn:Longitude');

				if( $results ) {
					for( $i = 0; $i < count( $results ); $i++ ) {
						$attributes = $results[$i]->attributes();

						$retVal['Placemarks'][$i]['Accuracy'] = (string)$attributes['precision'];
						$retVal['Placemarks'][$i]['Country'] = (string)$countries[$i];

						if( count($adminAreas) > $i && !empty($adminAreas[$i]) ) {
							$retVal['Placemarks'][$i]['AdministrativeArea'] = (string) $adminAreas[$i];
						}

						if( count($localities) > $i && !empty($localities[$i]) ) {
							$retVal['Placemarks'][$i]['Locality'] = (string) $localities[$i];
						}

						if( count($thoroughfares) > $i && !empty($thoroughfares[$i]) ) {
							$retVal['Placemarks'][$i]['Thoroughfare'] = (string) $thoroughfares[$i];
						}

						if( count($postalCodes) > $i && !empty($postalCodes[$i]) ) {
							$postalCode = explode( '-', $postalCodes[$i] );
							$retVal['Placemarks'][$i]['PostalCode'] = (string) $postalCode[0];
						}

						$retVal['Placemarks'][$i]['Latitude'] = (double)$latitudes[$i];
						$retVal['Placemarks'][$i]['Longitude'] = (double)$longitudes[$i];

					}
				}
			}
		}
		return $retVal;
	}
}

?>
