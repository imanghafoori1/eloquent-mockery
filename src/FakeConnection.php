<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;

class FakeConnection extends Connection implements ConnectionInterface
{
    public static function resolve($connection = null, $db = '', $prefix = '', $config = ['name' => 'arrayDB'])
    {
        return new FakeConnection(function () {
            return new FakePDO;
        }, $db, $prefix, $config);
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
        $query = $query->data;
        if (is_string($query)) {
            return parent::statement($query);
        }

        return $this->run($query['sql'], $bindings, function () use ($query) {
            if ($this->pretending()) {
                return true;
            }

            return (bool) FakeDB::insertGetId($query['value'], $query['builder']->from);
        });
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $query = $query->data;
        return $this->run($query['sql'], $bindings, function () use ($query) {
            if ($this->pretending()) {
                return [];
            }

            return FakeDb::exec($query);
        });
    }

    public function affectingStatement($query, $bindings = [])
    {
        $queryObj = $query;
        $query = $query->data;
        $type = $query['type'];
        if ('insertOrIgnore' === $type) {
            $this->insert($queryObj, $bindings);

            return Arr::isAssoc($query['value']) ? 1 : count($query['value']);
        }

        if (in_array($type, ['update', 'delete', 'truncate'])) {
            return $this->select($queryObj, $bindings);
        }

        if (is_array($query) && isset($query['uniqueBy'])) {
            $sql = $query['sql'];
            $query = $query['builder'];
            $values = $query['values'];
            $uniqueBy = $query['uniqueBy'];
        }
    }
}
