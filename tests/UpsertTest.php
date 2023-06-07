<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class UpsertModel extends Model
{
    protected $fillable = ['name'];

    protected $table = 'upserty';
}

class UpsertTest extends TestCase
{
    public function test_upsert()
    {
        if (! method_exists(Builder::class, 'upsert')) {
            $this->markTestSkipped();
        }
        FakeDB::mockQueryBuilder();

        UpsertModel::query()->upsert([
            'name' => 'a1',
            'age' => 12,
        ], ['name']);
        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 10,
        ], ['name']);
        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 11,
        ], ['name']);
        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 11,
        ], ['name', 'age']);

        $this->assertEquals(2, UpsertModel::query()->count());
        $upsert = UpsertModel::query()->get();
        $this->assertEquals(12, $upsert[0]->age);
        $this->assertEquals(11, $upsert[1]->age);

        FakeDB::dontMockQueryBuilder();
    }
}
