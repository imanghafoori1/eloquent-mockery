<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class EloquentMockeryServiceProvider extends ServiceProvider
{
    public function register()
    {
        Connection::resolverFor('arrayDB', function () {
            return FakeConnection::resolve();
        });
    }

    public function boot()
    {
        //
    }
}