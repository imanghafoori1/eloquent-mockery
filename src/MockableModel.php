<?php

namespace Imanghafoori\EloquentMockery;

use PHPUnit\Framework\Assert as PHPUnit;

trait MockableModel
{
    public static $saveCalls = [];

    public static $fakeDelete = false;

    public static $fakeCreate;

    public static $firstModel;

    public static $fakeRows = [];

    public static $fakeRelations = [];

    public static $deleteCalls = [];

    public static $ignoreWheres = false;

    public static $columnAliases = [];

    public static $forceMocks = [];

    public static function getSavedModelAttributes($index = 0)
    {
        return self::$saveCalls[$index] ?? [];
    }

    protected function performDeleteOnModel()
    {
        if (self::$fakeDelete === false) {
            parent::performDeleteOnModel();
        } else {
            self::$deleteCalls[] = $this;

            $this->exists = false;
        }
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

    public static function fakeDelete()
    {
        self::$fakeDelete = true;
    }

    public static function getDeletedModel($index = 0)
    {
        return self::$deleteCalls[$index] ?? null;
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
        return new class (self::class) extends FakeQueryBuilder {};
    }

    public static function stopFaking()
    {
        self::$fakeRows = [];
        self::$fakeCreate = null;
        self::$saveCalls = [];
        self::$firstModel = null;
        self::$fakeRelations = [];
        self::$deleteCalls = [];
        self::$ignoreWheres = false;
        self::$columnAliases = [];
        self::$forceMocks = [];
    }
}
