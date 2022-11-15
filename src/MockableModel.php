<?php

namespace Imanghafoori\EloquentMockery;

trait MockableModel
{
    public static $fakeMode = false;

    public static $forceMocks = [];

    public static function shouldRecieve($method)
    {
        return new class (self::class, $method){

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
        $callback = function ($model) {
            FakeDB::setChangedModel('softDeleted', $model);
        };
        if (method_exists(static::class, 'softDeleted')) {
            static::softDeleted($callback);
        } else {
            static::deleted($callback);
        }
    }

    public static function getUpdatedModel($index = 0)
    {
        return FakeDB::getChangedModel('updated', $index, static::class);
    }

    public static function getCreatedModel($index = 0)
    {
        return FakeDB::getChangedModel('created', $index, static::class);
    }

    public static function getSavedModel($index = 0)
    {
        return FakeDB::getChangedModel('saved', $index, static::class);
    }

    public static function getSoftDeletedModel($index = 0)
    {
        return FakeDB::getChangedModel('softDeleted', $index, static::class);
    }

    public static function getDeletedModel($index = 0)
    {
        return FakeDB::getChangedModel('deleted', $index, static::class);
    }

    public function newEloquentBuilder($query)
    {
        if ($this->isFakeMode()) {
            return new FakeEloquentBuilder($query, $this);
        }

        return parent::newEloquentBuilder($query);
    }

    public function getConnection()
    {
        if ($this->isFakeMode()) {
            return new FakeConnection();
        }

        return parent::getConnection();
    }

    public static function addFakeRow(array $attributes)
    {
        self::$fakeMode = true;
        FakeDB::addRow((new static())->getTable(), $attributes);
    }

    public static function fake()
    {
        self::$fakeMode = true;
    }

    public static function ignoreWheres()
    {
        FakeDB::$ignoreWheres = true;
    }

    public static function stopFaking()
    {
        self::$fakeMode = false;
        FakeDB::$fakeRows = [];
        FakeDB::$ignoreWheres = false;
        self::$forceMocks = [];
        FakeDB::$changedModels = [];
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
        if (! $this->isFakeMode()) {
            return parent::finishSave($options);
        }

        if ($this->wasRecentlyCreated) {
            FakeDB::setChangedModel('created', $this);
        }
        FakeDB::setChangedModel('saved', $this);

        return parent::finishSave($options);
    }
}
