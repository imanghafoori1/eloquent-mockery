<?php

namespace Imanghafoori\EloquentMockery;

use App\AddressModule\Models\Address;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

trait MockableModel
{
    public static $saveCalls = [];

    public static $fakeCreate;

    public static $firstModel;

    public static $fakeRows = [];

    public static $fakeRelations = [];

    public static $deleteCalls = [];

    public static $ignoreWheres = false;

    public static $columnAliases = [];

    public static function getSavedModelAttributes($index = 0)
    {
        return self::$saveCalls[$index] ?? [];
    }

    public static function getDeletedModelAttributes($index = 0)
    {
        return self::$deleteCalls[$index] ?? [];
    }

    public static function addRelation(string $relation, $model, array $row)
    {
        self::$fakeRelations[] = [$relation, $model, $row];
    }

    public static function fakeSave()
    {
        self::$saveCalls = [];
        self::saving(function ($model) {
            // we record the model attributes at the moment of being saved.
            self::$saveCalls[] = $model->getAttributes();

            // we return false to avoid hitting the database.
            return false;
        });
    }

    public static function fakeDelete()
    {
        self::$deleteCalls = [];
        self::deleting(function ($model) {
            // we record the model attributes at the moment of being deleted.
            self::$deleteCalls[] = $model->getAttributes();

            // we return false to avoid hitting the database.
            return false;
        });
    }

    public static function assertModelIsSaved($times = 1)
    {
        $actual = isset(self::$saveCalls) ? count(self::$saveCalls) : 0;

        PHPUnit::assertEquals($times, $actual, 'Model is not saved as expected.');
    }

    public static function assertModelIsNotDeleted($times = 1)
    {
        $actual = isset(self::$saveCalls) ? count(self::$saveCalls) : 0;

        PHPUnit::assertEquals($times, $actual, 'Model is not saved as expected.');
    }

    public static function query()
    {
        if (self::$fakeRows) {
            return self::fakeQueryBuilder();
        } else {
            return parent::query();
        }
    }

    public function newQuery()
    {
        if (self::$fakeRows) {
            return self::fakeQueryBuilder();
        } else {
            return parent::newQuery();
        }
    }

    public static function getCreateAttributes()
    {
        return self::$fakeCreate->createdModel->attributes;
    }

    public static function addFakeRow(array $attributes)
    {
        $row = [];
        foreach ($attributes as $key => $value) {
            $col = self::parseColumn($key);
            $row[$col] = $value;
        }
        self::$fakeRows[] = $row;
    }

    public static function ignoreWheres()
    {
        return self::$ignoreWheres = true;
    }

    private static function parseColumn($where)
    {
        if (! strpos($where,' as ')) {
            return $where;
        }

        [$tableCol, $alias] = explode(' as ', $where);
        self::$columnAliases[trim($tableCol)] = trim($alias);

        return $tableCol;
    }

    public static function fakeQueryBuilder()
    {
        return new class (self::class) extends Builder
        {
            public $recordedWheres = [];

            public $recordedWhereIn = [];

            public $recordedWhereNull = [];

            public $recordedWhereNotNull = [];

            public function __construct($originalModel)
            {
                $this->model = new $originalModel;
                $this->originalModel = $originalModel;
            }

            public function get($columns = ['*'])
            {
                $models = [];
                foreach (($this->originalModel)::$fakeRows as $i => $row) {
                    $model = new $this->originalModel;
                    $model->setRawAttributes($row);
                    foreach (($this->originalModel)::$fakeRelations as $j => [$relName, $relModel, $row]) {
                        $relModel = new $relModel;
                        $relModel->setRawAttributes($row[$i]);
                        $model->setRelation($relName, $relModel);
                    }
                    $models[] = $model;
                }

                return Collection::make($models);
            }

            public function first($columns = ['*'])
            {
                $data = $this->filter()->first();

                if (! $data) {
                    return $data;
                }

                $this->originalModel::unguard();

                $model = new $this->originalModel($data);
                $model->exists = true;

                ($this->originalModel)::$firstModel = $model;

                return $model;
            }

            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                $this->recordedWheres[] = [$column, $operator, $value];

                return $this;
            }

            public function whereIn($column, $values, $boolean = 'and', $not = false)
            {
                $this->recordedWhereIn[] = [$column, $values];

                return $this;
            }

            public function whereNull($column = null)
            {
                $this->recordedWhereNull[] = [$column];

                return $this;
            }

            public function select($columns = ['*'])
            {
                return $this;
            }

            public function whereNotNull($column = null)
            {
                $this->recordedWhereNotNull[] = [$column];

                return $this;
            }

            public function count()
            {
                return $this->filter()->count();
            }

            public function orderBy()
            {
                return $this;
            }

            public function join()
            {
                return $this;
            }

            public function leftJoin()
            {
                return $this;
            }

            public function rightJoin()
            {
                return $this;
            }

            public function innerJoin()
            {
                return $this;
            }

            public function create($data = [])
            {
                $model = clone $this->model;
                $model->exists = true;
                ($this->originalModel)::$saveCalls[] = $data;

                return $model;
            }

            private function filter()
            {
                $collection = collect(($this->originalModel)::$fakeRows);

                if ($this->originalModel::$ignoreWheres){
                    return $collection;
                }

                foreach ($this->recordedWheres as $_where) {
                    $_where = array_filter($_where, function ($val) {
                        return ! is_null($val);
                    });

                    $collection = $collection->where(...$_where);
                }

                foreach ($this->recordedWhereIn as $_where) {
                    $collection = $collection->whereIn($_where[0], $_where[1]);
                }

                foreach ($this->recordedWhereNull as $_where) {
                    $collection = $collection->whereNull($_where[0]);
                }

                foreach ($this->recordedWhereNotNull as $_where) {
                    $collection = $collection->whereNotNull($_where[0]);
                }

                return $collection
                    ->map(function ($item) {
                        return $this->_renameKeys_sa47rbt(
                            Arr::dot($item),
                            ($this->originalModel)::$columnAliases
                        );
                    });
            }

            private function _renameKeys_sa47rbt(array $array, array $replace)
            {
                $newArray = [];
                if (! $replace) {
                    return $array;
                }

                foreach ($array as $key => $value) {
                    $key = array_key_exists($key, $replace) ? $replace[$key] : $key;
                    $key = explode('.', $key);
                    $key = array_pop($key);
                    $newArray[$key] = $value;
                }

                return $newArray;
            }
        };
    }

    public static function stopFaking()
    {
        self::$fakeRows = [];
        self::$fakeCreate = null;
        self::$saveCalls = [];
        self::$firstModel = null;
    }
}
