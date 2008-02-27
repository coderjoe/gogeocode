<?php
/* SVN FILE: $Id$ */
/**
 * GoGeocode base abstract class
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

/**
 * Core base class for the various Geocoders
 */
abstract class BaseGeocode
{
	/**
	 * The service API key as set by the user
	 * @var string
	 * @access protected
	 */
	protected $apiKey;

	/**
	 * The earth's radius in a given unit system
	 * The default is 3963.1676 miles. The user can override this
	 * value with another value in annother system of measurement through
	 * the setEarthRadius() function.
	 *
	 * @var float
	 * @access protected
	 */
	protected $earthRadius;

	/**
	 * Basic public constructor which accepts an API key.
	 * The public constructor also sets the earth's radius to its default value
	 * in miles.
	 *
	 * @param string $key The geocoding service's API key
	 */
	public function __construct( $key ) {
		//Default to default unit of miles
		//by providing the earth radius in miles
		$this->setEarthRadius( 3963.1676 );
		$this->setKey( $key );
	}

	/**
	 * Modifier for the earth mean radius
	 *
	 * @param float $rad The new radius of the earth to use.
	 * @access public
	 */
	public function setEarthRadius( $rad ) {
		$this->earthRadius = $rad;
	}

	/**
	 * Modifier for the API key
	 *
	 * @param string $key The geocoding service API key to use.
	 * @access public
	 */
	public function setKey( $key ) {
		$this->apiKey = $key;
	}

	/**
	 * Load XML from an address
	 *
	 * @param string $address The address representing the XML source
	 * @access protected
	 */
	protected function loadXML( $address ) {
		$retVal = array();
		$contents = file_get_contents( $address );

		if( !empty( $http_response_header ) ) {
			$code = $http_response_header[0];
			$matches = array();
			preg_match('/^HTTP\/\d+\.\d+\s+(\d+)\s+[\w\s]+$/',$code, $matches);

			$retVal['response'] = $matches[1];
			$retVal['contents'] = $contents;
		}

		return $retVal;
	}

	/**
	 * Abstract function which will accept a string address
	 * and return an array of geocoded information for the given address.
	 *
	 * Return types for this function are mixed based on HTTP Response Codes:
	 *      Server not found: array()
	 *
	 *      404: array( 'Response' => array(
	 *                             'Status' => 404,
	 *                             'Request' => the subclass specific request
	 *                             )
	 *           );
	 *
	 *      200: The returned geocode information will be presented in the following format
	 *           While the example below only contains a single result, multiple results for a single
	 *           geocode request are possible and should be supported by subclasses
	 *
	 *           array( 'Response' => array(
	 *                             'Status' => ...
	 *                             'Request' => ...
	 *                             ),
	 *                  'Placemarks' => array(
	 *                                      array(
	 *                                        'Accuracy' => ...,
	 *                                        'Country'  => ...,
	 *                                        'AdministrativeArea' => ...,
	 *                                        'SubAdministrativeArea => ...,
	 *                                        'Locality' => ...,
	 *                                        'Thoroughfare' => ...,
	 *                                        'PostalCode' => ...,
	 *                                        'Latitude' => ...,
	 *                                        'Longitude' => ...
	 *                                      ),
	 *                                      array(
	 *                                        'Accuracy' => ...,
	 *                                        'Country' => ...,
	 *                                        .
	 *                                        .
	 *                                        .
	 *                                      )
	 *                                 )
	 *               )
	 *
	 * @param string $address A string representing the address the user wants decoded.
	 * @return array This function returns an array of geocoded location information for the given address.
	 * @access public
	 */
	abstract public function geocode( $address );

	/**
	 * Find the distance between the two latitude and longitude coordinates
	 * Where the latitude and longitude coordinates are in decimal degrees format.
	 *
	 * This function uses the haversine formula as published in the article
	 * "Virtues of the Haversine", Sky and Telescope, vol. 68 no. 2, 1984, p. 159
	 *
	 * References:
	 *         http://en.wikipedia.org/w/index.php?title=Haversine_formula&oldid=176737064
	 *         http://www.movable-type.co.uk/scripts/gis-faq-5.1.html
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by setEarthRadius() (default miles).
	 * @access public
	 */
	public function haversinDistance( $lat1, $long1, $lat2, $long2 )
	{
		$lat1 = deg2rad( $lat1 );
		$lat2 = deg2rad( $lat2 );
		$long1 = deg2rad( $long1);
		$long2 = deg2rad( $long2);

		$dlong = $long2 - $long1;
		$dlat = $lat2 - $lat1;

		$sinlat = sin( $dlat/2 );
		$sinlong = sin( $dlong/2 );

		$a = ($sinlat * $sinlat) + cos( $lat1 ) * cos( $lat2 ) * ($sinlong * $sinlong);
		$c = 2 * asin( min( 1, sqrt( $a ) ));

		return $this->earthRadius * $c;
	}

	/**
	 * Find the distance between two latitude and longitude points using the
	 * spherical law of cosines.
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by setEarthRadius() (default miles).
	 * @access public
	 */
	public function sphericalLawOfCosinesDistance( $lat1, $long1, $lat2, $long2 )
	{
		$lat1 = deg2rad( $lat1 );
		$lat2 = deg2rad( $lat2 );
		$long1 = deg2rad( $long1);
		$long2 = deg2rad( $long2);

		return $this->earthRadius * acos(
				sin( $lat1 ) * sin( $lat2 ) +
				cos( $lat1 ) * cos( $lat2 ) * cos( $long2 - $long1 )
			);
	}

	/**
	 * Find the distance between two latitude and longitude coordinates
	 * Where the latitude and the longitude coordinates are in decimal degrees format.
	 *
	 * @param float $lat1 The first coordinate's latitude
	 * @param float $ong1 The first coordinate's longitude
	 * @param float $lat2 The second coordinate's latitude
	 * @param float $long2 The second coordinate's longitude
	 * @return float The distance between the two points in the same unit as the earth radius as set by setEarthRadius() (default miles).
	 * @access public
	 */
	public function distanceBetween( $lat1, $long1, $lat2, $long2 )
	{
		return $this->haversinDistance( $lat1, $long1, $lat2, $long2 );
	}
}

?>
