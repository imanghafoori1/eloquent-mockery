<?php

namespace Imanghafoori\EloquentMockery;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
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

    public static $lastInsertedId;

    public static $deletedRows = [];

    public static function getLatestRow($table)
    {
        $row = [];

        foreach (FakeDB::$fakeRows[$table] ?? [] as $row) {
        }

        return $row;
    }

    public static function table($table)
    {
        return new class ($table){
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

    public static function truncate($query = null)
    {
        if ($query) {
            $table = $query['builder']->from;
            unset(self::$tables[$table]);
            unset(self::$fakeRows[$table]);
        } else {
            self::$fakeRows = [];
            self::$changedModels = [];
            self::$tables = [];
            self::$deletedRows = [];
        }
    }

    public static function mockQueryBuilder()
    {
        Connection::resolverFor('arrayDB', function ($connection, $db, $prefix, $config) {
            return FakeConnection::resolve($connection, $db, $prefix, $config);
        });

        $resolver = new ConnectionResolver(['arrayDB' => FakeConnection::resolve()]);
        $resolver->setDefaultConnection('arrayDB');
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
        self::$fakeRows[$table][$c] = [$table => $row];
        self::$lastInsertedId = $row['id'] ?? null;
        $c++;
        self::$tables[$table]['latestRowIndex'] = $c;
        self::$tables[$table]['latestRowId'] = self::$lastInsertedId;
    }

    public static function performJoins($base, $joins)
    {
        foreach ($joins as $join) {
            $joined = [];
            $type = '';
            $w = $join->wheres[0];
            $first = $w['first'];
            $second = $w['second'];
            $operator = $w['operator'];
            $table = $join->table;

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

    public static function sort($comparisons, Collection $collection)
    {
        $items = $collection->all();

        usort($items, function ($a, $b) use ($comparisons) {
            foreach ($comparisons as $comparison) {
                $comparison = Arr::wrap($comparison);

                $prop = $comparison[0];

                $ascending = Arr::get($comparison, 1, true) === true || Arr::get($comparison, 1, true) === 'asc';

                $result = 0;

                if (! is_string($prop) && is_callable($prop)) {
                    $result = $prop($a, $b);
                } else {
                    $values = [data_get($a, $prop), data_get($b, $prop)];

                    if (! $ascending) {
                        $values = array_reverse($values);
                    }

                    $result = $values[0] <=> $values[1];
                }

                if ($result === 0) {
                    continue;
                }

                return $result;
            }
        });

        return new Collection($items);
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

    public static function applyWheres($query, Collection $collection)
    {
        $basicOr = [];
        foreach (array_reverse($query->wheres) as $where) {
            $type = $where['type'];
            $table = $query->from;

            if ($type === 'Basic') {
                if ($where['boolean'] === 'or') {
                    $basicOr[] = $where;
                    continue;
                } elseif ($where['boolean'] === 'and' && $basicOr) {
                    $basicOr[] = $where;
                    $collection = self::applyBasicWheres($collection, $table, $basicOr);
                    $basicOr = [];

                    continue;
                }
                $collection = self::applyBasicWhere($where, $table, $query, $collection);
            } elseif ($type === 'Column' && $where['boolean'] === 'and') {
                $collection = $collection->filter(function ($row) use ($where, $table) {
                    return self::whereColumn($where, $row[$table]);
                });
            } elseif ($type === 'Nested' && $query !== $where['query']) {
                $collection = self::applyWheres($where['query'], $collection);
            } elseif (in_array($type, ['In', 'NotIn', 'Null', 'NotNull', 'between', 'InRaw', 'NotInRaw'])) {
                $value = $where['values'] ?? null;
                $type = str_replace('Raw', '', $type);
                $column = FakeDB::prefixColumn($where['column'], $table, $query->joins);
                $method = 'where'.$type;

                if ($type === 'between') {
                    $method = $where['not'] ? 'whereNotBetween' : 'whereBetween';
                }
                $collection = $collection->$method($column, $value);
            }
        }

        return $collection;
    }

    public static function isLike($value, $pattern, $item): bool
    {
        $pattern = str_replace('%', '.*', preg_quote($pattern, '/'));

        return (bool) (preg_match("/^{$pattern}$/i", data_get($item, $value) ?? ''));
    }

    public static function whereColumn($where, $row)
    {
        $operator = $where['operator'];
        $value1 = $row[$where['first']];
        $value2 = $row[$where['second']];

        if ($operator === '=' || $operator === '==') {
            $operator = '===';
        }

        if (in_array($operator, ['>', '>=', '<', '<=', '==='])) {
            return eval('return $value1 '.$operator.' $value2;');
        }
    }

    public static function operatorForWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);

            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    public static function lastInsertId()
    {
        return self::$lastInsertedId;
    }

    public static function prefixColumn($column, $mainTable, $joins)
    {
        if (! Str::contains($column, '.') && ! isset(FakeDB::$fakeRows[$mainTable][0][$mainTable][$column]) && $joins) {
            foreach ($joins as $joined) {
                $table = $joined->table;
                if (isset(FakeDB::$fakeRows[$table][0][$table][$column])) {
                    $column = $table.'.'.$column;
                }
            }
        }

        if (! Str::contains($column, '.')) {
            $column = $mainTable.'.'.$column;
        }

        return $column;
    }

    private static function applyBasicWhere($where, $table, $query, $collection)
    {
        $column = FakeDB::prefixColumn($where['column'], $table, $query->joins);

        if ($where['operator'] !== 'like') {
            return $collection->where($column, $where['operator'], $where['value']);
        }

        return $collection->filter(function ($item) use ($where, $column) {
            return FakeDB::isLike($column, $where['value'], $item);
        });
    }

    private static function applyBasicWheres($collection, $table, array $orWheres)
    {
        return $collection->filter(function ($item) use ($table, $orWheres) {
            foreach ($orWheres as $or) {
                if (self::operatorForWhere($table.'.'.$or['column'], $or['operator'], $or['value'])($item)) {
                    return true;
                }
            }

            return false;
        });
    }

    public static function filter($query, $columns = ['*']): Collection
    {
        $orderBy = $query->orders;
        $selects = $query->columns;
        $groups = $query->groups;
        $offset = $query->offset;
        $limit = $query->limit;
        $from = $query->from;
        $joins = $query->joins ?? [];
        $base = FakeDB::$fakeRows[$from] ?? [];
        $collection = FakeDB::performJoins($base, $joins);

        if ($groups) {
            foreach ($groups as &$group) {
                $group = $from.'.'.$group;
            }
            $collection = $collection->groupBy($groups);
        }

        foreach ($orderBy ?: [] as $i => $_order) {
            if (isset($_order['column'])) {
                $orderBy[$i]['column'] = FakeDB::prefixColumn($_order['column'], $from, $joins);
            }
        }

        $orderBy && ($collection = self::sortRows($collection, $orderBy));

        if (! FakeDB::$ignoreWheres) {
            $collection = FakeDB::applyWheres($query, $collection);
        }

        $collection = FakeDB::performSelects($collection, $columns, $selects, $from);

        $offset && $collection = $collection->skip($offset);

        $limit && $collection = $collection->take($limit);

        return $collection;
    }

    public static function sortRows(Collection $collection, $orderBy)
    {
        if (! $orderBy) {
            return $collection;
        }
        if (count($orderBy) === 1 && is_object($orderBy[0]['sql'] ?? '')) {
            $data = ($orderBy[0]['sql'])->data;
            if ($data['type'] === 'random') {
                return $collection->shuffle((int) $data['seed']);
            }
        }

        foreach ($orderBy as $ord) {
            $column = $ord['column'];
            $orderBy = $ord['direction'];
            $order[] = [$column, $orderBy];
        }

        return FakeDB::sort($order, $collection);
    }

    private static function handleExpressions($values): array
    {
        foreach ($values as $_key => $_val) {
            if (is_object($_val) && get_class($_val) === Expression::class) {
                $tokens = token_get_all('<?php '.$_val->getValue(new FakeGrammar()));
                array_shift($tokens);
                foreach ($tokens as $i => $token) {
                    $type = $token[0];
                    if ($type == T_CONSTANT_ENCAPSED_STRING) {
                        $tokens[$i][1] = '$values['.$token[1].']';
                    }
                }
                $code = '';
                foreach ($tokens as $token) {
                    $code = $code.($token[1] ?? $token[0]);
                }
                $code = 'return function ($values) { return '.$code.'; };';
                $values[$_key] = eval($code);
            }
        }

        return $values;
    }

    public static function update($query): int
    {
        $builder = $query['builder'];
        $values = self::handleExpressions($query['value']);
        $collection = FakeDB::filter($builder)->map(self::getRowUpdater($values));

        return FakeDB::syncTable($collection, $builder->from);
    }

    public static function upsert($query)
    {
        $values = $query['values'];
        $uniqueBy = $query['uniqueBy'];
        $query = $query['builder'];
        $from = $query->from;

        foreach ($values as $value) {
            $shouldInsert = true;
            $i = 0;
            foreach (self::$fakeRows[$from] ?? [] as $i => $_row) {
                $allMatch = true;
                foreach ($uniqueBy as $uniqueCol) {
                    if ($_row[$from][$uniqueCol] !== $value[$uniqueCol]) {
                        $allMatch = false;
                        break;
                    }
                }
                if ($allMatch) {
                    $shouldInsert = false;
                    break;
                }
            }

            if ($shouldInsert) {
                self::addRow($from, $value);
            } else {
                self::$fakeRows[$from][$i][$from] = $value;
            }
        }
    }

    public static function exec($query)
    {
        $type = $query['type'];

        return self::$type($query);
    }

    public static function exists($query)
    {
        $builder = $query['builder'];

        $value = (bool) self::aggregate($builder->columns, $builder, 'count')[0]['aggregate'];

        return [['exists' => $value]];
    }

    public static function select($query)
    {
        $builder = $query['builder'];

        if ($aggregate = $builder->aggregate) {
            $function = $aggregate['function'];
            $columns = $aggregate['columns'];

            return self::aggregate($columns, $builder, $function);
        }

        return self::filter($builder, $builder->columns)->values()->all();
    }

    private static function getRowUpdater(array $values): Closure
    {
        return function ($item) use ($values) {
            foreach ($values as $key => $value) {
                if ($value instanceof Closure) {
                    $values[$key] = $value($item);
                }
            }

            return $values + $item;
        };
    }

    public static function delete($query)
    {
        $query = $query['builder'];
        $rowsForDelete = FakeDB::filter($query);
        $from = $query->from;
        self::$deletedRows[$from][] = $rowsForDelete->values()->all();
        self::$fakeRows[$from] = array_diff_key(FakeDB::$fakeRows[$from] ?? [], $rowsForDelete->all());

        return $rowsForDelete->count();
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

    private static function aggregate($columns, $builder, $function)
    {
        if ($columns !== '*') {
            foreach ((array) $columns as $column) {
                $builder->whereNotNull($column);
            }
        }

        $result = ['aggregate' => self::filter($builder, ['*'])->$function($columns)];

        return [0 => $result];
    }
}
