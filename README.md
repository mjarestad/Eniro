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

    // option 1
    Eniro::get('mcdonalds');

    // option 2
    Eniro::query('mcdonalds')->get();

Search and get the first result

    Eniro::query('mcdonalds')->first();

Search by a specific Eniro id

    Eniro::find(123456);

Search a specific area

    Eniro::area('Stockholm')->get('mcdonalds');

Offset and limit

    Eniro::skip(25)->take(25)->get('mcdonalds');

Define a country

    Eniro::country('se')->get('mcdonalds');

Add a callback function for jsonp

    Eniro::callback('callbackFunction')->get('mcdonalds');

By default it returns an php object. If you would like json or array you can use the following method

    Eniro::search('mcdonalds')->toJson();
    Eniro::search('mcdonalds')->toArray();

To count the number of results

    Eniro::search('mcdonalds')->count();

##Coming soon

Testing...