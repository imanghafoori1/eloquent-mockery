<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Query\Builder;

class FakeQueryBuilder extends Builder
{
    public $orderBy = [];

    public $shuffle = false;

    private $dates = [];

    public $recordedJoin = [];

    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBy = [FakeDB::prefixColumn($column, $this->from, $this->joins), $direction];

        return parent::orderBy($column, $direction);
    }

    public function crossJoin($table, $first = null, $operator = null, $second = null)
    {
        return $this;
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
    {
        $this->recordedJoin[] = [$table, $first, $operator, $second, $type];

        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function filterRows($sort = true, $columns = ['*'])
    {
        $base = FakeDB::$fakeRows[$this->from] ?? [];
        $collection = FakeDB::performJoins($base, $this->recordedJoin);

        $sort && ($collection = $this->sortRows($collection));

        if (! FakeDB::$ignoreWheres) {
            $collection = FakeDB::applyWheres($this, $collection);
        }

        $collection = FakeDB::performSelects($collection, $columns, $this->columns, $this->from);

        $this->offset && $collection = $collection->skip($this->offset);

        $this->limit && $collection = $collection->take($this->limit);

        return $collection;
    }

    public function get($columns = ['*'])
    {
        return $this->filterRows(true, $columns)->values();
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        $collection = $this->filterRows()->map(function ($item) use ($amount, $column) {
            $item[$column] =  $item[$column] + $amount;

            return $item;
        });

        return FakeDB::syncTable($collection, $this->from);
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        return $this->increment($column, $amount * -1);
    }

    public function pluck($column, $key = null)
    {
        return $this->filterRows()->pluck($column, $key);
    }

    public function aggregate($function, $columns = ['*'])
    {
        return $this->filterRows(false)->$function($columns);
    }

    public function exists()
    {
        return $this->count() > 0;
    }

    public function addSelect($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->columns = array_merge($this->columns ?? [], $columns);

        return $this;
    }

    public function inRandomOrder($seed = '')
    {
        return $this->shuffle = [true, ($seed ?: null)];
    }

    public function reorder($column = null, $direction = 'asc')
    {
        $this->orderBy = [FakeDB::prefixColumn($column, $this->from, $this->joins), $direction];

        return $this;
    }

    public function sortRows($collection)
    {
        if ($this->orderBy) {
            $column = $this->orderBy[0];
            $isDates = in_array($column, $this->dates);
            $collection = FakeDB::sort($column, $collection, $this->orderBy[1], $isDates);
        } elseif ($this->shuffle !== false) {
            $collection->shuffle($this->shuffle[1]);
        }

        return $collection;
    }

    public function count($columns = '*')
    {
        if ($columns !== '*') {
            foreach ((array) $columns as $column) {
                $this->whereNotNull($column);
            }
        }

        return $this->filterRows(false)->count();
    }

    public function addFakeRow(string $table, $val, $key)
    {
        FakeDB::changeFakeRow($table, $val, $key);
    }

    public function forNestedWhere()
    {
        return $this;
    }
}
