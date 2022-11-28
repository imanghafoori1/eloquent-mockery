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

    public function delete($query, $bindings = [])
    {
        parent::delete($query['sql'], $bindings);
        $query = $query['builder'];
        $rowsForDelete = $query->filterRows();
        $from = $query->from;
        $count = $rowsForDelete->count();
        FakeDB::$fakeRows[$from] = array_diff_key(FakeDB::$fakeRows[$from] ?? [], $rowsForDelete->all());

        return $count;
    }

    public function update($query, $bindings = [])
    {
        parent::update($query['sql'], $bindings);
        $values = $query['value'];
        $builder = $query['builder'];

        $collection = $builder->filterRows()->map(function ($item) use ($values) {
            return $values + $item;
        });

        return FakeDB::syncTable($collection, $builder->from);
    }

    public function insert($query, $bindings = [])
    {
        parent::insert($query['sql'], $bindings);
        $builder = $query['builder'];

        return (bool) self::insertGetId($query['value'], $builder->from);
    }

    public static function insertGetId(array $values, $table)
    {
        if (! Arr::isAssoc($values)) {
            foreach ($values as $value) {
                self::insertGetId($value, $table);
            }
            return true;
        }

        if (! isset($values['id'])) {
            $values['id'] = (FakeDB::$tables[$table]['latestRowId'] ?? 0) + 1;
        }

        FakeDB::addRow($table, $values);

        return $values['id'];
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $sql = $query['sql'];
        $query = $query['builder'];

        parent::select($sql, $bindings, $useReadPdo);

        return $query->filterRows(true, $query->columns)->values()->all();
    }

    public function affectingStatement($query, $bindings = [])
    {
        if ('insertOrIgnore' === ($query['type'] ?? '')) {
            $this->insert($query, $bindings);
            $values = $query['value'];

            return Arr::isAssoc($values) ? 1 : count($values);
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
