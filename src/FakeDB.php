<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FakeDB
{
    public static $ignoreWheres = false;

    public static $fakeRows = [];

    public static $changedModels = [];

    public static $tables = [];

    private static $originalConnection;

    public static function getLatestRow($table)
    {
        $row = [];

        foreach (FakeDB::$fakeRows[$table] ?? [] as $row) {
        }

        return $row;
    }

    public static function table($table)
    {
        return new class ($table) {
            private $table;

            public function __construct($table)
            {
                $this->table = $table;
            }

            public function addRow($row)
            {
                FakeDB::addRow($this->table, $row);
            }
        };
    }

    public static function getChangedModel(string $action, $index, $model)
    {
        return FakeDB::$changedModels[$model][$action][$index] ?? null;
    }

    public static function setChangedModel(string $action, $model)
    {
        FakeDB::$changedModels[get_class($model)][$action][] = $model;
    }

    public static function truncate()
    {
        self::$fakeRows = [];
        self::$changedModels = [];
        self::$tables = [];
    }

    public static function mockQueryBuilder()
    {
        Connection::resolverFor('fakeDb', function () {
            return new FakeConnection();
        });

        $resolver = new ConnectionResolver(['fakeDb' => new FakeConnection]);
        $resolver->setDefaultConnection('fakeDb');
        self::$originalConnection = Model::getConnectionResolver();
        Model::setConnectionResolver($resolver);
    }

    public static function dontMockQueryBuilder()
    {
        self::truncate();
        self::$originalConnection && Model::setConnectionResolver(self::$originalConnection);
    }

    public static function addRow(string $table, array $row)
    {
        $c = self::$tables[$table]['latestRowIndex'] ?? 0;
        FakeDB::$fakeRows[$table][$c] = [$table => $row];
        $c++;
        self::$tables[$table]['latestRowIndex'] = $c;
    }

    public static function performJoins($base, $joins)
    {
        foreach ($joins as $join) {
            $joined = [];
            [$table, $first, $operator, $second, $type] = $join;
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
                if ($type === 'left') {
                    $joined[] = $row1 + [$table2 => array_fill_keys(array_keys($row2[$table2]), null)];
                }
            }
            $base = $joined;
        }

        return collect($base);
    }

    public static function sort($column, $collection, $sortBy, $isDate)
    {
        if (! $isDate) {
            $sortBy = ($sortBy === 'desc' ? 'sortByDesc' : 'sortBy');

            return $collection->$sortBy($column);
        }

        return $collection->sort(function ($item1, $item2) use ($column, $sortBy) {
            $direction = ($sortBy === 'desc' ? 1 : -1);

            return (strtotime($item2[$column]) <=> strtotime($item1[$column])) * $direction;
        });
    }

    private static function parseSelects($columns, $selects)
    {
        $columns = (array) $columns;
        if ($columns === ['*'] && $selects) {
            $columns = [];
        }

        $cols = array_merge($selects ?: [], $columns);

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

    public static function performSelects($collection, $columns, $selects, $_table)
    {
        [$columns, $aliases] = self::parseSelects($columns, $selects);

        return $collection->map(function ($item) use ($columns, $aliases, $_table) {
            if ($columns !== ['*']) {
                foreach ($columns as $i => $col) {
                    ! Str::contains($col, '.') && $columns[$i] = $_table.'.'.$col;
                }
                $newItem = [];
                foreach ($columns as $col) {
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
                $item = self::aliasColumns($aliases, $item, $_table);
            }

            return self::removeTableName(Arr::dot($item));
        });
    }

    public static function aliasColumns($aliases, $item, $table)
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

    public static function removeTableName(array $array)
    {
        $newArray = [];

        foreach ($array as $key => $value) {
            $key = explode('.', $key);
            $key = array_pop($key);
            $newArray[$key] = $value;
        }

        return $newArray;
    }

    public static function syncTable(Collection $collection, $table)
    {
        $collection->each(function ($val, $key) use ($table) {
            // rename keys: table.column to column.
            $newVal = [];
            foreach ($val as $k => $v) {
                $newVal[str_replace($table.'.', '', $k)] = $v;
            }
            self::changeFakeRow($table, $newVal, $key);
        });

        return $collection->count();
    }

    public static function changeFakeRow(string $table, $val, $key): void
    {
        FakeDB::$fakeRows[$table][$key] = [$table => $val];
    }

    public static function applyWheres($query, $collection)
    {
        foreach ($query->recordedWhereBetween as $_where) {
            $collection = $collection->whereBetween(...$_where);
        }

        foreach ($query->recordedWhereNotBetween as $_where) {
            $collection = $collection->whereNotBetween(...$_where);
        }

        foreach ($query->recordedWheres as $_where) {
            $_where = array_filter($_where, function ($val) {
                return ! is_null($val);
            });

            if ($_where[1] === 'like') {
                $collection = $collection->filter(function ($item) use ($_where) {
                    return FakeDB::isLike($_where[0], $_where[2], $item);
                });
            } else {
                $collection = $collection->where(...$_where);
            }
        }

        foreach ($query->recordedWhereIn as $_where) {
            $collection = $collection->whereIn($_where[0], $_where[1]);
        }

        foreach ($query->recordedWhereNotIn as $_where) {
            $collection = $collection->whereNotIn($_where[0], $_where[1]);
        }

        foreach ($query->recordedWhereNull as $_where) {
            $collection = $collection->whereNull($_where[0]);
        }

        foreach ($query->recordedWhereNotNull as $_where) {
            $collection = $collection->whereNotNull($_where[0]);
        }

        return $collection;
    }

    public static function isLike($value, $pattern, $item): bool
    {
        $pattern = str_replace('%', '.*', preg_quote($pattern, '/'));

        return (bool) preg_match("/^{$pattern}$/i", data_get($item, $value) ?? '');
    }
}
