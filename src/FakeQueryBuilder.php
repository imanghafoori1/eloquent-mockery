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

    public $recordedWhereLikes = [];

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
        $column = $this->prefixColumn($column);
        $this->recordedWhereNotIn[] = [$column, $values];

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

    public function join(
        $table,
        $first,
        $operator = null,
        $second = null,
        $type = 'inner',
        $where = false
    ) {
        $this->recordedJoin[] = [$table, $first, $operator, $second];

        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second);
    }

    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $column = $this->prefixColumn($column);

        if ($operator === 'like') {
            $this->recordedWhereLikes[] = [$column, $value];
        } else {
            $this->recordedWheres[] = [$column, $operator, $value];
        }

        return $this;
    }

    public function whereNull($columns, $boolean = 'and', $not = false)
    {
        $columns = $this->prefixColumn($columns);

        $this->recordedWhereNull[] = [$columns];

        return $this;
    }

    public function whereNotNull($columns, $boolean = 'and')
    {
        $columns = $this->prefixColumn($columns);

        $this->recordedWhereNotNull[] = [$columns];

        return $this;
    }

    public function whereBetween($column, iterable $values, $boolean = 'and', $not = false)
    {
        $column = $this->prefixColumn($column);
        $this->recordedWhereBetween[] = [$column, $values];

        return $this;
    }

    public function whereNotBetween($column, iterable $values, $boolean = 'and')
    {
        $column = $this->prefixColumn($column);
        $this->recordedWhereNotBetween[] = [$column, $values];

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

        $collection->each(function ($val, $key) {
            // rename keys: table.column to column.
            foreach ($val as $k => $v) {
                $k1 = str_replace($this->from.'.', '', $k);
                unset($val[$k]);
                $val[$k1] = $v;
            }
            $this->addFakeRow($this->from, $val, $key);
        });

        return $collection->count();
    }

    public function filterRows($sort = true, $columns = ['*'])
    {
        $base = FakeDB::$fakeRows[$this->from] ?? [];
        $collection = $this->performJoins($base);

        $sort && ($collection = $this->sortRows($collection));

        if (! FakeDB::$ignoreWheres) {
            $collection = $this->applyWheres($collection);
        }

        [$cols, $aliases] = $this->parseSelects($columns);

        $collection = $collection->map(function ($item) use ($cols, $aliases) {
            if ($cols !== ['*']) {
                foreach ($cols as $i => $col) {
                    ! Str::contains($col, '.') && $cols[$i] = $this->from.'.'.$col;
                }
                $newItem = [];
                foreach ($cols as $col) {
                    [$table, $c] = explode('.', $col);
                    if (array_key_exists($c, $item[$table])) {
                        $newItem[$table][$c] = $item[$table][$c];
                    } elseif ($c === '*') {
                        $newItem[$table] = $item[$table];
                    }
                }
                $item = $newItem;
            }

            if ($aliases) {
                $item = $this->aliasColumns($aliases, $item, $this->from);
            }

            return $this->_renameKeys(Arr::dot($item), FakeDB::$columnAliases[$this->from] ?? []);
        });

        $this->offset && $collection = $collection->skip($this->offset);

        $this->limit && $collection = $collection->take($this->limit);

        return $collection;
    }

    private function _renameKeys(array $array, array $replace)
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            $key = array_key_exists($key, $replace) ? $replace[$key] : $key;
            $key = explode('.', $key);
            $key = array_pop($key);
            $newArray[$key] = $value;
        }

        return $newArray;
    }

    public function get($columns = ['*'])
    {
        return $this->filterRows(true, $columns)->values();
    }

    public function insertGetId(array $values, $sequence = null)
    {
        foreach (FakeDB::$fakeRows[$this->from] ?? [] as $row) {
        }

        return ($row[$this->from]['id'] ?? 0) + 1;
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
        return $this->filterRows(false)->count() > 0;
    }

    public function inRandomOrder($seed = '')
    {
        return $this->shuffle = [true, ($seed ?: null)];
    }

    public function reorder($column = null, $direction = 'asc')
    {
        $column = $this->prefixColumn($column);
        $this->orderBy = [$column, $direction];

        return $this;
    }

    public function sortRows($collection)
    {
        if ($this->orderBy) {
            $sortBy = ($this->orderBy[1] === 'desc' ? 'sortByDesc' : 'sortBy');
            $column = $this->orderBy[0];

            if (in_array($column, $this->dates)) {
                $collection = $collection->sort(function ($t, $item) use ($column) {
                    $direction = ($this->orderBy[1] === 'desc' ? 1 : -1);

                    return (strtotime($item[$column]) <=> strtotime($t[$column])) * $direction;
                });
            } else {
                $collection = $collection->$sortBy($column);
            }
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

    private function parseSelects($columns)
    {
        $columns = (array) $columns;
        if ($columns === ['*'] && $this->columns) {
            $columns = [];
        }

        $cols = array_merge($this->columns ?: [], $columns);

        $aliases = [];
        foreach ($cols as $i => $col) {
            $segments = explode(' as ', $col);
            if (count($segments) === 2) {
                [$tableCol, $alias] = $segments;
                $aliases[trim($alias)] = trim($tableCol);
                $cols[$i] = trim($tableCol);
            }
        }

        return [$cols, $aliases];
    }

    public function aliasColumns($aliases, $item, $table)
    {
        foreach ($aliases as $alias => $col) {
            $segments = explode('.', $col);

            if (count($segments) === 1) {
                $segments = [$table, $segments[0]];
            }
            $value = $item[$segments[0]][$segments[1]];
            unset($item[$segments[0]][$segments[1]]);
            $item[$segments[0]][$alias] = $value;
        }

        return $item;
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

    public function addFakeRow(string $table, $val, $key): void
    {
        FakeDB::$fakeRows[$table][$key] = [$table => $val];
    }

    private function performJoins($base)
    {
        foreach ($this->recordedJoin as $join) {
            $joined = [];
            [$table, $first, $operator, $second] = $join;
            [$table1, $columns1] = explode('.', $first);
            [$table2, $columns2] = explode('.', $second);
            if ($table === $table1) {
                [$table1, $table2] = [$table2, $table1];
                [$columns1, $columns2] = [$columns2, $columns1];
            }
            foreach ($base as $row1) {
                foreach (FakeDB::$fakeRows[$table2] ?? [] as $row2) {
                    if ($row1[$table1][$columns1] == $row2[$table2][$columns2]) {
                        $joined[] = $row1 + $row2;
                    }
                }
            }
            $base = $joined;
        }

        return collect($base);
    }

    public function isLike($like, $item): bool
    {
        $pattern = str_replace('%', '.*', preg_quote($like[1], '/'));

        return (bool) preg_match("/^{$pattern}$/i", data_get($item, $like[0]) ?? '');
    }

    private function applyWheres($collection)
    {
        foreach ($this->recordedWhereBetween as $_where) {
            $collection = $collection->whereBetween(...$_where);
        }

        foreach ($this->recordedWhereNotBetween as $_where) {
            $collection = $collection->whereNotBetween(...$_where);
        }

        foreach ($this->recordedWheres as $_where) {
            $_where = array_filter($_where, function ($val) {
                return ! is_null($val);
            });

            $collection = $collection->where(...$_where);
        }

        foreach ($this->recordedWhereLikes as $like) {
            $collection = $collection->filter(function ($item) use ($like) {
                return $this->isLike($like, $item);
            });
        }

        foreach ($this->recordedWhereIn as $_where) {
            $collection = $collection->whereIn($_where[0], $_where[1]);
        }

        foreach ($this->recordedWhereNotIn as $_where) {
            $collection = $collection->whereNotIn($_where[0], $_where[1]);
        }

        foreach ($this->recordedWhereNull as $_where) {
            $collection = $collection->whereNull($_where[0]);
        }

        foreach ($this->recordedWhereNotNull as $_where) {
            $collection = $collection->whereNotNull($_where[0]);
        }

        return $collection;
    }
}
