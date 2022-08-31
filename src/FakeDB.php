<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FakeDB
{
    public static $ignoreWheres = false;

    public static $fakeRows = [];

    public static $columnAliases = [];

    private static $originalConnection;

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

    public static function truncate()
    {
        self::$fakeRows = [];
    }

    public static function mockQueryBuilder()
    {
        self::$originalConnection = config()->get('database.default');
        config()->set('database.default', 'fakeDB');
        config()->set('database.connections.fakeDB', []);

        DB::extend('fakeDB', function () {
            return new FakeConnection();
        });
    }

    public static function stopMockQueryBuider()
    {
        config()->set('database.default', self::$originalConnection);
    }

    public static function addRow(string $table, array $row)
    {
        FakeDB::$fakeRows[$table][] = [$table => $row];
    }

    public static function performJoins($base, $joins)
    {
        foreach ($joins as $join) {
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

            return self::_renameKeys(Arr::dot($item), FakeDB::$columnAliases[$_table] ?? []);
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

    public static function _renameKeys(array $array, array $replace)
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

    public static function syncTable(Collection $collection, $table)
    {
        $collection->each(function ($val, $key) use ($table){
            // rename keys: table.column to column.
            foreach ($val as $k => $v) {
                $k1 = str_replace($table.'.', '', $k);
                unset($val[$k]);
                $val[$k1] = $v;
            }
            self::changeFakeRow($table, $val, $key);
        });

        return $collection->count();
    }

    public static function changeFakeRow(string $table, $val, $key): void
    {
        FakeDB::$fakeRows[$table][$key] = [$table => $val];
    }
}
