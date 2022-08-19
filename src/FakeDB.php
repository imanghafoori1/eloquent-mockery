<?php

namespace Imanghafoori\EloquentMockery;

use Illuminate\Support\Facades\DB;

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
                FakeDB::$fakeRows[$this->table][] = $row;
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
}
