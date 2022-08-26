<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;

class FakeConnection extends Connection implements ConnectionInterface
{
    public function __construct()
    {
        //
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        return $callback();
    }

    public function query()
    {
        return new FakeQueryBuilder($this);
    }
}
