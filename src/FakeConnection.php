<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class FakeConnection extends Connection implements ConnectionInterface
{
    protected $pretending = true;

    public static function resolve()
    {
        return new FakeConnection(function () {
            return new FakePDO;
        });
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        return $callback();
    }

    public function query()
    {
        return new FakeQueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    protected function getDefaultQueryGrammar()
    {
        return new FakeGrammar;
    }

    public function statement($query, $bindings = [])
    {
        return $this->run($query['sql'], $bindings, function () use ($query) {
            return (bool) FakeDB::insertGetId($query['value'], $query['builder']->from);
        });
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query['sql'], $bindings, function () use ($query) {
            return FakeDb::exec($query);
        });
    }

    public function affectingStatement($query, $bindings = [])
    {
        if ('insertOrIgnore' === ($query['type'] ?? '')) {
            $this->insert($query, $bindings);
            $values = $query['value'];

            return Arr::isAssoc($values) ? 1 : count($values);
        }

        if (in_array($query['type'] ?? '', ['update', 'delete'])) {
            return $this->run($query['sql'], $bindings, function () use ($query) {
                return FakeDb::exec($query);
            });
        }

        if (is_array($query) && isset($query['uniqueBy'])) {
            $sql = $query['sql'];
            $query = $query['builder'];
            $values = $query['values'];
            $uniqueBy = $query['uniqueBy'];
        }

        parent::affectingStatement($query, $bindings);
    }
}
