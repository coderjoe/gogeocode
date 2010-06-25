<?php
/* SVN FILE: $Id$ */
/**
 * GoGeocode object for use with Google's Geocoding API
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
 * Geocoder object for use with Google's geocoding API
 */
class GoogleGeocode extends BaseGeocode
{
	/*
	 * Status code information grokked from:
	 * http://code.google.com/apis/maps/documentation/reference.html#GGeoStatusCode
	 */

	/**
	 * Status Code:
	 * No errors occurred; the address was successfully parsed and its geocode has been returned.
	 * @var int
	 * @access public
	 */
	const SUCCESS = 200;

	/**
	 * Status Code:
	 * HTTP Status Code 404 Not Found
	 * @var int
	 * @access public
	 */
	const NOT_FOUND = 404;


	/**
	 * Status Code:
	 * A directions request could not be successfully parsed.
	 * @var int
	 * @access public
	 */
	const BAD_REQUEST = 400;

	/**
	 * Status Code:
	 * A geocoding or directions request could not be successfully processed,
	 * yet the exact reason for the failure is not known.
	 * @var int
	 * @access public
	 */
	const SERVER_ERROR = 500;

	/**
	 * Status Code:
	 * The HTTP q parameter was either missing or had no value.
	 * For geocoding requests, this means that an empty address was specified as input.
	 * For directions requests, this means that no query was specified in the input.
	 * @var int
	 * @access public
	 */
	const MISSING_QUERY = 601;

	/**
	 * Status Code:
	 * Synonym for MISSING_QUERY.
	 * @var int
	 * @access public
	 */
	const MISSING_ADDRESS = 601;

	/**
	 * Status Code:
	 * No corresponding geographic location could be found for the specified address.
	 * This may be due to the fact that the address is relatively new, or it may be incorrect.
	 * @var int
	 * @access public
	 */
	const UNKNOWN_ADDRESS = 602;

	/**
	 * Status Code:
	 * The geocode for the given address or the route for the given directions query
	 * cannot be returned due to legal or contractual reasons.
	 * @var int
	 * @access public
	 */
	const UNAVAILABLE_ADDRESS = 603;

	/**
	 * Status Code:
	 * The GDirections object could not compute directions between the points mentioned
	 * in the query. This is usually because there is no route available between the two
	 * points, or because we do not have data for routing in that region.
	 * @var int
	 * @access public
	 */
	const UNKNOWN_DIRECTIONS = 604;

	/**
	 * Status Code:
	 * The given key is either invalid or does not match the domain for which it was given.
	 * @var int
	 * @access public
	 */
	const BAD_KEY = 610;

	/**
	 * Status Code:
	 * The given key has gone over the requests limit in the 24 hour period.
	 * @var int
	 * @access public
	 */
	const TOO_MANY_QUERIES = 620;

	/**
	 * Geocode the provided API. See BaseGeocode::geocode for detailed information
	 * about this function's return type.
	 *
	 * @param string $address The string address to retrieve geocode information about
	 * @return array An empty array on server not found. Otherwise an array of geocoded location information.
	 */
	public function geocode( $address )
	{
		$retVal = array();
		$url = "http://maps.google.com/maps/geo?q=";
		$url .= urlencode( $address ) . "&output=xml&oe=UTF-8&key=" . $this->apiKey;

		$nsKml = 'http://earth.google.com/kml/2.0';
		$nsUrn = 'urn:oasis:names:tc:ciq:xsdschema:xAL:2.0';

		$file = $this->loadXML( $url );

		if( empty( $file ) ) {
			return $retVal;
		}

		$retVal['Response'] = array( 
					'Status' => (int)$file['response'],
					'Request' => 'geo'
				);

		if( $file['response'] == 200 ) {
			$xml = new SimpleXMLElement( $file['contents'] );

			$xml->registerXPathNamespace( 'kml', $nsKml );
			$xml->registerXPathNamespace( 'urn', $nsUrn );

			//Now that we have the google request, and we succeeded in getting a response
			//from the server, lets replace oure response portion with the google response
			$retVal['Response']['Status'] = (int)$xml->Response->Status->code;
			$retVal['Response']['Request'] = (string)$xml->Response->Status->request;

			$retVal['Placemarks'] = array();
			if( $xml && $retVal['Response']['Status'] == GoogleGeocode::SUCCESS )
			{
				$placemarks = $xml->xpath('//kml:Placemark');
				$countries = $xml->xpath('//urn:CountryNameCode');
				$adminAreas = $xml->xpath('//urn:AdministrativeAreaName');
				$subAdminAreas = $xml->xpath('//urn:SubAdministrativeAreaName');
				$localities = $xml->xpath('//urn:LocalityName');
				$thoroughfares = $xml->xpath('//urn:ThoroughfareName');
				$postalCodes = $xml->xpath('//urn:PostalCodeNumber');

				for( $i = 0; $i < count( $placemarks ); $i++ )
				{
					list($longitude, $latitude) = explode( ',' , $placemarks[$i]->Point->coordinates );
					$attributes = $placemarks[$i]->AddressDetails->attributes();

					$retVal['Placemarks'][$i] = array();
					$retVal['Placemarks'][$i]['Accuracy']	= (int)$attributes['Accuracy'];
					$retVal['Placemarks'][$i]['Country'] = (string)$countries[$i];

					if( count( $adminAreas ) > $i ) {
						$retVal['Placemarks'][$i]['AdministrativeArea'] = (string)$adminAreas[$i];
					}

					if( count( $subAdminAreas ) > $i ) {
						$retVal['Placemarks'][$i]['SubAdministrativeArea'] = (string)$subAdminAreas[$i];
					}

					if( count( $localities ) > $i ) {
						$retVal['Placemarks'][$i]['Locality'] = (string)$localities[$i];
					}

					if( count( $thoroughfares ) > $i ) {
						$retVal['Placemarks'][$i]['Thoroughfare'] = (string)$thoroughfares[$i];
					}

					if( count( $postalCodes ) > $i ) {
						$retVal['Placemarks'][$i]['PostalCode'] = (string)$postalCodes[$i];
					}

					$retVal['Placemarks'][$i]['Latitude']= (double)$latitude;
					$retVal['Placemarks'][$i]['Longitude'] = (double)$longitude;
				}
			}
		}
		return $retVal;
	}
}

?>
