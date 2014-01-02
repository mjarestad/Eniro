<?php namespace Mjarestad\Eniro\Facades;

use Illuminate\Support\Facades\Facade;

class Eniro extends Facade
{
    protected static function getFacadeAccessor() { return 'eniro'; }
}