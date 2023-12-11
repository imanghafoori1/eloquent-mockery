<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Schema\Blueprint;
use Imanghafoori\EloquentMockery\FakeConnection;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class CreateDropTableTest_ extends TestCase
{
    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    /**
     * @_test
     */
    public function create_and_drop_tables()
    {
        $schema = FakeConnection::resolve()->getSchemaBuilder();

        if (! method_exists($schema, 'getAllTables')) {
            $this->markTestSkipped('getAllTables is removed');
        }
        $this->assertEquals([], $schema->getAllTables());

        $schema->create('users_o_o', function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('id', true);
            $blueprint->string('name')->comment('comment');
        });

        $this->assertEquals(['users_o_o'], $schema->getAllTables());

        // test the "hasColumn" method:
        $this->assertTrue($schema->hasColumn('users_o_o', 'id'));
        $this->assertTrue($schema->hasColumn('users_o_o', 'name'));
        $this->assertFalse($schema->hasColumn('users_o_o', 'absent'));

        // test the "getColumnListing" method:
        $this->assertEquals(['id', 'name'], $schema->getColumnListing('users_o_o'));

        if (method_exists($schema, 'dropColumns')) {
            $this->testDropColumns($schema);
        }

        $this->renameTest($schema);

        $this->dropAllTablesTest($schema);
    }

    private function testDropColumns($schema)
    {
        $schema->dropColumns('users_o_o', ['name']);

        $this->assertEquals(['id'], $schema->getColumnListing('users_o_o'));

        $this->assertTrue($schema->hasTable('users_o_o'));
        $this->assertFalse($schema->hasTable('users_q_q'));
    }

    private function renameTest($schema)
    {
        $schema->rename('users_o_o', 'users_u_u');

        $this->assertTrue($schema->hasTable('users_u_u'));
        $this->assertFalse($schema->hasTable('users_o_o'));
    }

    private function dropAllTablesTest($schema)
    {
        $schema->drop('users_u_u');

        $this->assertFalse($schema->hasTable('users_u_u'));

        $schema->dropIfExists('users_u_u');

        $this->makeSampleTable($schema, 'users_o_o');

        $schema->dropIfExists('users_o_o');

        $this->makeSampleTable($schema, 'users_o_o');
        $this->makeSampleTable($schema, 'users_u_u');

        $this->assertTrue($schema->hasColumn('users_u_u', 'id'));
        $this->assertFalse($schema->hasColumn('users_u_u', 'name'));

        $this->assertEquals(['id'], $schema->getColumnListing('users_u_u'));

        // Act:
        $schema->dropAllTables();

        // Assert:
        $this->assertFalse($schema->hasTable('users_o_o'));
        $this->assertFalse($schema->hasTable('users_u_u'));
    }

    private function makeSampleTable($schema, string $table)
    {
        $schema->create($table, function (Blueprint $blueprint) {
            $blueprint->unsignedInteger('id', true);
        });
    }
}
