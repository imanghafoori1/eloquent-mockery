<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Support\Arr;

class FakeConnection extends Connection implements ConnectionInterface
{
    public static function resolve($connection = null, $db = '', $prefix = '', $config = ['name' => 'arrayDB'])
    {
        $fakeConnection = new FakeConnection(function () {
            return new FakePDO;
        }, $db, $prefix, $config);

        $fakeConnection->setQueryGrammar(new FakeGrammar);
        
        return $fakeConnection;
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

        $pretending = true;

        return $this->run($query['sql'], $bindings, function () use ($query, $pretending) {
            if ($this->pretending()) {
                return $pretending;
            }

            return (bool) FakeDB::insertGetId($query['value'], $query['builder']->from);
        });
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runFake($query->data, $bindings, []);
    }

    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        return $this->runFake($query->data, $bindings, []);
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

        if (in_array($type, ['update', 'delete', 'truncate', 'upsert'])) {
            return $this->runFake($query, $bindings, 0);
        }
    }

    protected function runFake($data, $bindings, $pretend)
    {
        return $this->run($data['sql'], $bindings, function () use ($data, $pretend, $bindings) {
            if ($this->pretending()) {
                return $pretend;
            }

            $data['bindings'] = $bindings;

            return FakeDb::exec($data);
        });
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        $grammar = new FakeSchemaGrammar();

        return $this->withTablePrefix($grammar);
    }
}
