# Bumbal-Geocoder
Bumbal GeoCoder for turning address data into coordinates. 


## Requirements

PHP 7 and later

## Installation & Usage
### Composer

To install the bindings via [Composer](http://getcomposer.org/), add the following to `composer.json`:

```
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/freightlive/bumbal-geocode.git"
        }
    ],
    "require": {
        "bumbal/bumbal-geocode-php": "*@dev",
    }
}
```

Then run `composer install`

## Basic Usage

Different providers can be fed into the GeoCoder through the `GeoProviderList` class. In priority order, these providers will try to geocode an `Address`.

Results are given a precision value in the range 0 to 1. They can be filtered out by providing the threshold `$precision` parameter in `$geo_coder->getLatLngResultListFromAddress($address, $precision)`.

`$geo_coder->getLatLngResultListFromAddress($address, $precision)` returns a `LatLngResultList`, which is composed of a list of `LatLngResult` (in precision order) and optional log and errors.

Currently there are two Geocoding providers implemented in this package: Google Maps and OpenStreetMap through GraphHopper. 

```php
$google_provider = new \BumbalGeocode\Providers\Google\GoogleGeoProvider('google_maps_api_key');
$graphhopper_provider = new \BumbalGeocode\Providers\OSMGraphHopper\OSMGraphHopperGeoProvider('graphhopper_api_key');

$geo_coder = new \BumbalGeocode\GeoCoder(new \BumbalGeocode\GeoProviderList([
    $google_provider,
    $graphhopper_provider
]));

$address = new \BumbalGeocode\Model\Address([
    'street' => 'Nieuwlandstraat',
    'house_nr' => '48',
    'zipcode' => '5038 SP',
    'city' => 'Tilburg',
    'iso_country' => 'NL'
]);

$result = $geo_coder->getLatLngResultListFromAddress($address, 0.6);
var_dump($result);
```

## Advanced Usage

### GeoCoderOptions

The `GeoCoderOptions` class currently has two options to influence the results given back by the `GeoCoder`.

- quit_on_error: If any error is encountered (e.g. an external provider's endpoint can't be reached), processing is stopped immediately and the `LatLngResultList` is returned. Default value is `FALSE`.
- quit_after_first_result: If a provider returns at least one satisfactory result measured by the `$precision` parameter, processing is stopped immediately and the `LatLngResultList` is returned. Default value is `TRUE`.

#### Example
```php
$google_provider = new \BumbalGeocode\Providers\Google\GoogleGeoProvider('google_maps_api_key');

$coder_options = new \BumbalGeocode\Model\GeoCoderOptions([
    'quit_on_error' => TRUE,
    'quit_after_first_result' => FALSE
]);
        
$geo_coder = new \BumbalGeocode\GeoCoder(new \BumbalGeocode\GeoProviderList([
    $google_provider,
]), $coder_options);

```


### GeoProviderOptions

The `GeoProviderOptions` class currently has two options that determine the logging and error data returned in the `LatLngResultList` result. 
Note that these options are set per provider, but their results are combined in the `LatLngResultList` result returned by `$geo_coder->getLatLngResultListFromAddress($address, $precision)`.

- log_errors: Default value is `TRUE`.
- log_debug: Default value is `FALSE`.

#### Example
```php
$provider_options = new \BumbalGeocode\Model\GeoProviderOptions([
    'log_debug' => TRUE,
    'log_errors' => TRUE
]);

$google_provider = new \BumbalGeocode\Providers\Google\GoogleGeoProvider('google_maps_api_key', $provider_options);
        
$geo_coder = new \BumbalGeocode\GeoCoder(new \BumbalGeocode\GeoProviderList([
    $google_provider
]));

```

### GeoProviderList

The `GeoProviderList` class has some methods to control provider priority and query contained providers.

#### Setting providers

- Passing providers in constructor will set provider priority to the order in which providers were presented.
- Adding a provider through the `setProvider(GeoProvider $provider, int $priority = 0)` method will insert the provider according to the `$priority` parameter. When two providers are inserted with the same priority, the last one added will take precedence.

#### Getting providers

- `getProviders()` will return all providers in priority and precedence order.
- `getProviders(int $priority)` will return all providers with `$priority` in precedence order.
- `getProvider(int $index)` will return the provider on position `$index` in the list.



### GeoResponseAnalyser

`GeoProvider` implementations can use an instance of `GeoResponseAnalyser` to analyse and value results.

`GeoResponseAnalyser` is meant to be subclassed for use with a particular `GeoProvider` implementation. In this subclass methods should be implemented to analyse and value a particular aspect of a single result as queried by the provider as follows:
- The method names should comply to the regexp `^getValue[A-Z]`
- The method signature should be `protected function getValueCamelCase(mixed $single_result, Address $address)`
- The returned result should be a `float` in the range 0..1

The result of all these methods will be weighed and combined into a final result value. Weights can be set through the `GeoResponseAnalyser` subclass constructor.

What weight is applied to what result is determined by the weight key. This is an uncamelcased version of the `getValueXXX` method name. All available weight keys in a particular `GeoResponseAnalyser` subclass can be queried with the `getKeys()` method.

For example: the result of a method named `getValuePositionOnMap` will have a weight key `position_on_map`.

#### Example
```php
 $google_precision_analyser = new \BumbalGeocode\Providers\Google\GoogleGeoResponseAnalyser([
    'result_types' => 1,
    'location_type' => 1,
    'address_components_equals' => 3,
    'address_components_similarity' => 5,
    'bounding_box' => 2,
]);

$google_provider = new \BumbalGeocode\Providers\Google\GoogleGeoProvider('google_maps_api_key', NULL, $google_precision_analyser);
```

This means that in the final combined result, the result of the `getValueAddressComponentsSimilarity` method will be valued five times as much as the result of `getValueResultTypes`.

Weight keys that aren't set will have a default value of 0.0, the corresponding method will not be executed.

## Tweaking the GeoCoding Results 