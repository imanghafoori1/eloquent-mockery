<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Schema\Blueprint;
use Imanghafoori\EloquentMockery\FakeConnection;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\FakeSchemaBuilder;
use PHPUnit\Framework\TestCase;

class CreateDropTableTest extends TestCase
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
     * @test
     */
    public function schema()
    {
        $schema = FakeConnection::resolve()->getSchemaBuilder();

        $schema->create('users_o_o', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
        });

        $this->assertTrue($schema->hasColumn('users_o_o', 'id'));
        $this->assertTrue($schema->hasColumn('users_o_o', 'name'));

        $this->assertEquals(['id', 'name'], $schema->getColumnListing('users_o_o'));

        $schema->dropColumns('users_o_o', ['name']);

        $this->assertEquals(['id'], $schema->getColumnListing('users_o_o'));

        $this->assertTrue($schema->hasTable('users_o_o'));
        $this->assertFalse($schema->hasTable('users_q_q'));

        $schema->rename('users_o_o', 'users_u_u');

        $this->assertTrue($schema->hasTable('users_u_u'));
        $this->assertFalse($schema->hasTable('users_o_o'));

        $this->dropAllTablesTest($schema);
    }

    private function dropAllTablesTest(FakeSchemaBuilder $schema): void
    {
        $schema->drop('users_u_u');

        $this->assertFalse($schema->hasTable('users_u_u'));

        $schema->dropIfExists('users_u_u');

        $schema->create('users_o_o', function (Blueprint $blueprint) {
            $blueprint->id();
        });

        $schema->dropIfExists('users_o_o');

        $schema->create('users_o_o', function (Blueprint $blueprint) {
            $blueprint->id();
        });
        $schema->create('users_u_u', function (Blueprint $blueprint) {
            $blueprint->id();
        });

        $this->assertTrue($schema->hasColumn('users_u_u', 'id'));
        $this->assertFalse($schema->hasColumn('users_u_u', 'name'));

        $this->assertEquals(['id'], $schema->getColumnListing('users_u_u'));

        $schema->dropAllTables();

        $this->assertFalse($schema->hasTable('users_o_o'));
        $this->assertFalse($schema->hasTable('users_u_u'));
    }
}
