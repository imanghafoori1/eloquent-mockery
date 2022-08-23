<?php

namespace Imanghafoori\EloquentMockery;

trait MockableModel
{
    public static $changedModels = [
        'updated' => [],
        'saved' => [],
        'created' => [],
        'deleted' => [],
        'softDeleted' => [],
    ];

    public static $fakeMode = false;

    public static $forceMocks = [];

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
                $this->theClass::$forceMocks[$this->method][] = $value;
            }
        };
    }

    public static function fakeSoftDelete()
    {
        static::$fakeMode = true;
        $cb = function ($model) {
            self::$changedModels['softDeleted'][] = $model;
        };
        if (method_exists(static::class, 'softDeleted')) {
            static::softDeleted($cb);
        } else {
            static::deleted($cb);
        }
    }

    public static function getUpdatedModel($index = 0)
    {
        return self::$changedModels['updated'][$index] ?? null;
    }

    public static function getCreatedModel($index = 0)
    {
        return self::$changedModels['created'][$index] ?? null;
    }

    public static function getSavedModel($index = 0)
    {
        return self::$changedModels['saved'][$index] ?? null;
    }

    public static function getSoftDeletedModel($index = 0)
    {
        return self::$changedModels['softDeleted'][$index] ?? null;
    }

    public static function getDeletedModel($index = 0)
    {
        return self::$changedModels['deleted'][$index] ?? null;
    }

    public function newEloquentBuilder($query)
    {
        if ($this->isFakeMode()) {
            return new FakeEloquentBuilder($this, static::class);
        } else {
            return parent::newEloquentBuilder($query);
        }
    }

    protected function newBaseQueryBuilder()
    {
        if ($this->isFakeMode()) {
            return new FakeQueryBuilder($this->getDates());
        } else {
            return parent::newBaseQueryBuilder();
        }
    }

    public function getConnection()
    {
        if ($this->isFakeMode()) {
            return new FakeConnection();
        } else {
            return parent::getConnection();
        }
    }

    public static function addFakeRow(array $attributes)
    {
        $table = (new static())->getTable();
        $row = [];
        self::$fakeMode = true;
        foreach ($attributes as $key => $value) {
            $col = self::parseColumn($key, $table);
            $row[$col] = $value;
        }
        FakeDB::$fakeRows[$table][] = $row;
    }

    public static function fake()
    {
        self::$fakeMode = true;
    }

    public static function ignoreWheres()
    {
        FakeDB::$ignoreWheres = true;
    }

    private static function parseColumn($where, $table)
    {
        if (! strpos($where,' as ')) {
            return $where;
        }

        [$tableCol, $alias] = explode(' as ', $where);
        FakeDB::$columnAliases[$table][trim($tableCol)] = trim($alias);

        return $tableCol;
    }

    public function fakeEloquentBuilder()
    {
        return new FakeEloquentBuilder($this, static::class);
    }

    public static function stopFaking()
    {
        self::$fakeMode = false;
        FakeDB::$fakeRows = [];
        FakeDB::$ignoreWheres = false;
        FakeDB::$columnAliases = [];
        self::$forceMocks = [];
        self::$changedModels = [
            'updated' => [],
            'saved' => [],
            'created' => [],
            'deleted' => [],
            'softDeleted' => [],
        ];
    }

    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    private function isFakeMode()
    {
        return FakeDB::$fakeRows || self::$fakeMode || isset(FakeDB::$fakeRows[$this->getTable()]);
    }

    protected function finishSave(array $options)
    {
        if ($this->isFakeMode()) {
            if ($this->wasRecentlyCreated) {
                static::$changedModels['created'][] = $this;
                FakeEloquentBuilder::insertRow($this->getAttributes(), $this->getTable());
            }
            static::$changedModels['saved'][] = $this;
        }

        return parent::finishSave($options);
    }
}
