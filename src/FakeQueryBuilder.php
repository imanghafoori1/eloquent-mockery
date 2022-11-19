<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FakeQueryBuilder extends Builder
{
    public $recordedWheres = [];

    public $recordedWhereIn = [];

    public $recordedWhereNotIn = [];

    public $recordedWhereNull = [];

    public $recordedWhereNotNull = [];

    public $orderBy = [];

    public $recordedWhereBetween = [];

    public $recordedWhereNotBetween = [];

    public $shuffle = false;

    private $dates;

    private $recordedJoin = [];

    public function __construct(ConnectionInterface $connection = null, $dates = [])
    {
        $this->connection = ($connection ?: new FakeConnection());
        $this->dates = $dates;
    }

    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $column = $this->prefixColumn($column);
        if ($not) {
            $this->recordedWhereNotIn[] = [$column, $values];
        } else {
            $this->recordedWhereIn[] = [$column, $values];
        }

        return $this;
    }

    public function whereNotIn($column, $values, $boolean = 'and', $not = false)
    {
        $this->recordedWhereNotIn[] = [$this->prefixColumn($column), $values];

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBy = [$this->prefixColumn($column), $direction];

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

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        if ($column instanceof \Closure && is_null($operator)) {
            $column($this);

            return $this;
        }

        $column = $this->prefixColumn($column);

        $this->recordedWheres[] = [$column, $operator, $value];

        return $this;
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $this->recordedWhereNull[] = [$this->prefixColumn($columns)];

        return $this;
    }

    public function whereNotNull($columns, $boolean = 'and')
    {
        $this->recordedWhereNotNull[] = [$this->prefixColumn($columns)];

        return $this;
    }

    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        $this->recordedWhereBetween[] = [$this->prefixColumn($column), $values];

        return $this;
    }

    public function whereNotBetween($column, iterable $values, $boolean = 'and')
    {
        $this->recordedWhereNotBetween[] = [$this->prefixColumn($column), $values];

        return $this;
    }

    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (! is_null($id)) {
            $this->where($this->from.'.id', '=', $id);
        }

        $rowsForDelete = $this->filterRows();
        $count = $rowsForDelete->count();
        FakeDB::$fakeRows[$this->from] = array_diff_key(FakeDB::$fakeRows[$this->from] ?? [], $rowsForDelete->all());

        return $count;
    }

    public function update(array $values)
    {
        $collection = $this->filterRows()->map(function ($item) use ($values) {
            return $values + $item;
        });

        return FakeDB::syncTable($collection, $this->from);
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

    public function insertGetId(array $values, $sequence = null)
    {
        if (! Arr::isAssoc($values)) {
            foreach ($values as $value) {
                $this->insertGetId($value);
            }
            return true;
        }

        if (! isset($values['id'])) {
            $row = FakeDB::getLatestRow($this->from);
            $values['id'] = ($row[$this->from]['id'] ?? 0) + 1;
        }

        FakeDB::addRow($this->from, $values);

        return $values['id'];
    }

    public function insert(array $values)
    {
        $this->insertGetId($values);

        return true;
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
        $this->orderBy = [$this->prefixColumn($column), $direction];

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

    private function prefixColumn($column)
    {
        if (! Str::contains($column, '.') && ! isset(FakeDB::$fakeRows[$this->from][0][$this->from][$column])) {
            foreach ($this->recordedJoin as $joined) {
                [$table] = $joined;
                if (isset(FakeDB::$fakeRows[$table][0][$table][$column])) {
                    $column = $table.'.'.$column;
                }
            }
        }

        if (! Str::contains($column, '.')) {
            $column = $this->from.'.'.$column;
        }

        return $column;
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
