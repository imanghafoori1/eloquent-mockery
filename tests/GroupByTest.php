<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class GroupByModel extends Model
{
    protected $table = 'my_tbl';
}

class GroupByTest extends TestCase
{
    public function setUp(): void
    {
        FakeDB::mockQueryBuilder();
    }

    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
    }

    /**
     * @test
     */
    public function groupByBasicTest()
    {
        FakeDB::addRow('my_tbl', ['id' => 1,'col1' => 'v1']);
        FakeDB::addRow('my_tbl', ['id' => 2,'col1' => 'v2']);
        FakeDB::addRow('my_tbl', ['id' => 3,'col1' => 'v1']);
        FakeDB::addRow('my_tbl', ['id' => 4,'col1' => 'v3']);
        FakeDB::addRow('my_tbl', ['id' => 5,'col1' => 'v2']);

        $rows = GroupByModel::query()->groupBy('col1')->get();

        $this->assertCount(3, $rows);

        $this->assertEquals(3, $rows[0]->id);
        $this->assertEquals(5, $rows[1]->id);
        $this->assertEquals(4, $rows[2]->id);
    }
}
