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

    public static $firstModel;

    public static $fakeRows = [];

    public static $ignoreWheres = false;

    public static $columnAliases = [];

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
        static::softDeleted(function ($model) {
            self::$changedModels['softDeleted'][] = $model;
        });
    }

    public static function getUpdatedModel($index = 0)
    {
        return self::$changedModels['updated'][$index] ?? null;
    }

    public static function getCreatedModel($index = 0)
    {
        return self::$changedModels['created'][$index] ?? null;
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
            return new FakeQueryBuilder($this);
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
        $row = [];
        self::$fakeMode = true;
        foreach ($attributes as $key => $value) {
            $col = self::parseColumn($key);
            $row[$col] = $value;
        }
        self::$fakeRows[] = $row;
    }

    public static function fake()
    {
        self::$fakeMode = true;
    }

    public static function ignoreWheres()
    {
        self::$ignoreWheres = true;
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

    public function fakeEloquentBuilder()
    {
        return new FakeEloquentBuilder($this, static::class);
    }

    public static function stopFaking()
    {
        self::$fakeMode = false;
        self::$fakeRows = [];
        self::$firstModel = null;
        self::$ignoreWheres = false;
        self::$columnAliases = [];
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
        return self::$fakeRows || self::$fakeMode;
    }
}
