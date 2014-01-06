Eniro
=====

A wrapper for Eniro Company Search API to get laravel-like syntax in Laravel 4.

##Installation

Install through composer. Add require `mjarestad/eniro` to your `composer.json`

    "require": {
        "mjarestad/eniro": "dev-master"
    }
    
###Laravel 4

Add the ServiceProvider to the providers array in `app/config/app.php`

    'Mjarestad\Eniro\EniroServiceProvider',
    
Add the Facade to the aliases array in `app/config/app.php`

    'Eniro'  => 'Mjarestad\Eniro\Facades\Eniro',

##Usage

Simple search

    Eniro::search('mcdonalds');

Search a specific area

    Eniro::area('Stockholm')->search('mcdonalds');

Offset and limit

    Eniro::skip(25)->take(25)->search('mcdonalds');

Define a country

    Eniro::country('se')->search('mcdonalds');

Add a callback function for jsonp

    Eniro::callback('callbackFunction')->search('mcdonalds');

By default it returns an php object. If you would like json or array you can use the following method

    Eniro::search('mcdonalds')->toJson();
    Eniro::search('mcdonalds')->toArray();

To count the number of results

    Eniro::search('mcdonalds')->count();

To search a specific Eniro id

    Eniro::find(123456);

##Coming soon

Testing...