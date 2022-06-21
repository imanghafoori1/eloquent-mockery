<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Database\Eloquent\Builder;

trait MockableModel
{
    public static $saveCount = [];

    public static function getSavedAttributes($index)
    {
        return self::$saveCount[self::class][$index] ?? [];
    }

    public static function fakeSave()
    {
        self::saving(function ($model) {
            if (isset(self::$saveCount[self::class])) {
                self::$saveCount[self::class] = [$model->getAttributes()];
            } else {
                self::$saveCount[self::class][] = $model->getAttributes();
            }

            return false;
        });
    }

    public static function getSaveCount()
    {
        return isset(self::$saveCount[self::class]) ? count(self::$saveCount[self::class]) : 0;
    }

    public static function fakeCreate()
    {
        self::$fake = new class (self::class) extends Builder
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
        if (self::$rows) {
            return self::fakeRow();
        } elseif (self::$fake) {
            return self::$fake;
        } else {
            return parent::query();
        }
    }

    public function newQuery()
    {
        return self::query();
    }

    public static function getCreateAttributes()
    {
        return self::$fake->createdModel->attributes;
    }

    public static $fake;

    public static $firstModel;

    public static $rows;

    public static function addRow(array $attributes)
    {
        self::$rows[] = $attributes;
    }

    public static function fakeRow()
    {
        return new class (self::class) extends Builder
        {
            public $recordedWheres = [];

            /**
             * @var \Illuminate\Database\Eloquent\HigherOrderBuilderProxy|mixed
             */
            private $recordedWhereIn = [];

            public function __construct($originalModel)
            {
                $this->originalModel = $originalModel;
            }

            public function first($columns = ['*'])
            {
                $collection = collect(($this->originalModel)::$rows);
                foreach ($this->recordedWheres as $_where) {
                    $_where = array_filter($_where);
                    $collection = $collection->where($_where[0], $_where[1] ?? null);
                }

                foreach ($this->recordedWhereIn as $_where) {
                    $collection = $collection->whereIn($_where[0], $_where[1] ?? null);
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
        };
    }
}
