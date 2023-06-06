<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class TruncateTest extends TestCase
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
    public function truncateWorks()
    {
        TruncatyModel::setEventDispatcher(new Dispatcher());
        FakeDB::addRow('truncate_tbl', ['id' => 1, 'name' => 'hi 1']);
        FakeDB::addRow('truncate_tbl', ['id' => 2, 'name' => 'hi 2']);

        TruncatyModel::query()->truncate();
        $this->assertTrue(TruncatyModel::all()->isEmpty());

        $s = TruncatyModel::query()->create(['name' => 'a']);
        $this->assertEquals(1, $s->id);

        TruncatyModel::query()->truncate();
        $s = TruncatyModel::query()->create(['name' => 'a']);
        $this->assertEquals(1, $s->id);
    }
}

class TruncatyModel extends Model
{
    protected $fillable = ['name'];

    protected $table = 'truncate_tbl';
}
