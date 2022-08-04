<?php

namespace Imanghafoori\EloquentMockery;

use PHPUnit\Framework\Assert as PHPUnit;

trait MockableModel
{
    public static $saveCalls = [];

    public static $fakeMode = false;

    public static $fakeCreate;

    public static $firstModel;

    public static $fakeRows = [];

    public static $fakeRelations = [];

    public static $deletedModels = [];

    public static $ignoreWheres = false;

    public static $columnAliases = [];

    public static $forceMocks = [];

    public static $softDeletedModels = [];

    public static function getSavedModelAttributes($index = 0)
    {
        return self::$saveCalls[$index] ?? [];
    }

    public static function shouldRecieve($method)
    {
        return new class (self::class, $method) {

            private $theClass;

            private $method;

            public function __construct($class, $method)
            {
                $this->theClass = $class;
                $this->method = $method;
            }

            public function andReturn($value)
            {
                ($this->theClass)::$forceMocks[$this->method][] = $value;
            }
        };
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

    public static function fakeSoftDelete()
    {
        static::$fakeMode = true;
        static::softDeleted(function ($model) {
            self::$softDeletedModels[] = $model;
        });
    }

    public static function getSoftDeletedModel($index = 0)
    {
        return self::$softDeletedModels[$index] ?? null;
    }

    public static function getDeletedModel($index = 0)
    {
        return self::$deletedModels[$index] ?? null;
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

    public static function getCreateAttributes()
    {
        return self::$fakeCreate->createdModel->attributes;
    }

    public function newEloquentBuilder($query)
    {
        if (self::$fakeRows || self::$fakeMode) {
            return new FakeEloquentBuilder($this, static::class);
        } else {
            return parent::newEloquentBuilder($query);
        }
    }

    protected function newBaseQueryBuilder()
    {
        if (self::$fakeRows || self::$fakeMode) {
            return new FakeQueryBuilder(static::class);
        } else {
            return parent::newBaseQueryBuilder();
        }
    }

    public function getConnection()
    {
        if (self::$fakeRows || self::$fakeMode) {
            return new FakeConnection();
        } else {
            return parent::getConnection();
        }
    }

    public static function addFakeRow(array $attributes)
    {
        $row = [];
        self::$fakeMode = true;
        foreach ($attributes as $key => $value) {
            $col = self::parseColumn($key);
            $row[$col] = $value;
        }
        self::$fakeRows[] = $row;
    }

    public static function fakeDelete()
    {
        self::$fakeMode = true;
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

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getKeyName();
    }

    public static function fakeQueryBuilder()
    {
        return new FakeEloquentBuilder(static::class);
    }

    public static function stopFaking()
    {
        self::$fakeMode = false;
        self::$fakeRows = [];
        self::$fakeCreate = null;
        self::$saveCalls = [];
        self::$firstModel = null;
        self::$fakeRelations = [];
        self::$deletedModels = [];
        self::$ignoreWheres = false;
        self::$columnAliases = [];
        self::$forceMocks = [];
    }
}
