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

    public static function getSavedModelAttributes($index = 0)
    {
        return self::$saveCalls[$index] ?? [];
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

    public static function assertModelIsSaved($times = 1)
    {
        $actual = isset(self::$saveCalls) ? count(self::$saveCalls) : 0;

        PHPUnit::assertEquals($times, $actual, 'Model is not saved as expected.');
    }

    public static function fakeCreate()
    {
        self::$fakeCreate = new class (self::class) extends Builder
        {
            public $originalModel;

            public $createdModel;

            public function __construct($originalModel)
            {
                $this->originalModel = $originalModel;
            }

            public function create(array $attributes = [])
            {
                return $this->createdModel = new $this->originalModel($attributes);
            }
        };
    }

    public static function query()
    {
        if (self::$fakeRows) {
            return self::fakeQueryBuilder();
        } elseif (self::$fakeCreate) {
            return self::$fakeCreate;
        } else {
            return parent::query();
        }
    }

    public function newQuery()
    {
        if (self::$fakeRows) {
            return self::fakeQueryBuilder();
        } elseif (self::$fakeCreate) {
            return self::$fakeCreate;
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
        self::$fakeRows[] = $attributes;
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
                $collection = collect(($this->originalModel)::$fakeRows);
                foreach ($this->recordedWheres as $_where) {
                    $_where = array_filter($_where);
                    $collection = $collection->where($_where[0], $_where[1] ?? null);
                }

                foreach ($this->recordedWhereIn as $_where) {
                    $collection = $collection->whereIn($_where[0], $_where[1] ?? null);
                }

                foreach ($this->recordedWhereNull as $_where) {
                    $collection = $collection->whereNull($_where[0]);
                }

                foreach ($this->recordedWhereNotNull as $_where) {
                    $collection = $collection->whereNotNull($_where[0]);
                }

                $data = $collection->first();

                if (! $data) {
                    return parent::first();
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

            public function whereNotNull($column = null)
            {
                $this->recordedWhereNotNull[] = [$column];

                return $this;
            }

            public function orderBy()
            {
                return $this;
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

    public function andReturn($rows)
    {
        foreach ($rows as $row) {
            self::addFakeRow($row);
        }
    }
}
