Introduction
============

*I no longer have time to maintain this library, as such it has suffered some bitrot and no longer functions against the Google Geocoding API. Pull requests to fix this issue are welcome but for now I'd consider this library dead.*

GoGeocode is a small set of PHP classes that were developed to simplify the process of geocoding data using both the Google v2 geocoding api and Yahoo geocoding services.

While it's true that many PHP based geocoding classes exist, very few make use of the extensive data returned by the geocoding services. In many cases only the latitude and longitude coordinates are returned

GoGeocode is designed to geocode a given location while preserving as much of the returned data as possible, and providing it in a PHP friendly array format.

Requirements
============
  * PHP 5
  * SimpleXML Extension

Instructions
============

To use GoGeocode to geocode simply declare the GoGeocode object of your choice and use the geocode function.

Example (Geocode an address using Google's geocoding service)
-------------------------------------------------------------


    require('GoogleGeocode.php');

    $apiKey = 'your_api_key_here';
    $geo = new GoogleGeocode( $apiKey );

    $result = $geo->geocode( "1 Lomb Memorial Drive Rochester NY, 14623" );

    print_r( $result );

Which would output:

    Array
    (
        [Response] => Array
            (
                [Status] => 200
                [Request] => geocode
            )
    
        [Placemarks] => Array
            (
                [0] => Array
                    (
                        [Accuracy] => 8
                        [Country] => US
                        [AdministrativeArea] => NY
                        [SubAdministrativeArea] => Monroe
                        [Locality] => Rochester
                        [Thoroughfare] => 1 Lomb Memorial Dr
                        [PostalCode] => 14623
                        [Latitude] => 43.092108
                        [Longitude] => -77.675238
                    )
    
            )
    
    )

*Note:* Any implementation using GoGeocode should check the status result of the geocode request to detect if any requests are being denied due to server error or rate limiting.

Gotchas
=======
Each geocoding API, while similar has its own subtle differences.

Google, for example, always returns a HTTP status of 200, with the XML data signifying success in its node structure. Yahoo on the other hand signifies the status of the request using the HTTP status codes, indicating the type of failure or success in the data returned by the web service.

In order to simplify the process GoGeocode standardizes on terminology and on operational decisions.

Terminology
-----------
  * Response
    * A Response is the status and request information for a given geocode request
  * Placemark
    * A Placemark is a distinct point on a map represented by a latitude and longitude
  * Placemark Members
    * All members of a placemark follow the nomenclature set forth by the xAL or [http://www.oasis-open.org/committees/ciq/ciq.html#6 eXtensible Address Language]
    * Specific definitions used in GoGeocode include: country, administrative area, sub-administrative area, locality, thoroughfare, postal code, latitude, and longitude.

Results
-------
GoGeocode objects will always return an object regardless of request status. The returned object will contain the response's status. The service's GoGeocode object will provide a number of class constants for use in defining the geocode request's status.

If the status represents success, then the object returned will contain an array of placemark data.
