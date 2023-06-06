<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class EloquentMockeryServiceProvider extends ServiceProvider
{
    public function register()
    {
        Connection::resolverFor('arrayDB', function ($connection, $db, $prefix, $config) {
            return FakeConnection::resolve($connection, $db, $prefix, $config);
        });
    }

    public function boot()
    {
        //
    }
}