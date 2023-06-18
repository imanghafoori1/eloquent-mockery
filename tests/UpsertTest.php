<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
            'weight' => 50,
        ], ['name']);

        $this->assertEquals(1, UpsertModel::query()->count());

        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 10,
            'weight' => 60,
        ], ['name']);

        // Assert is inserted:
        $this->assertEquals(2, UpsertModel::query()->count());

        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 11,
            'weight' => 66,
        ], ['name']);

        // Assert is updated:
        $this->assertEquals(2, UpsertModel::query()->count());

        $upsert = UpsertModel::query()->get();
        $this->assertEquals(66, $upsert[1]->weight);
        $this->assertEquals(11, $upsert[1]->age);
        $this->assertEquals(Carbon::now()->format('Y-m-d H:i'), $upsert[1]->created_at->format('Y-m-d H:i'));
        $this->assertEquals(Carbon::now()->format('Y-m-d H:i'), $upsert[1]->updated_at->format('Y-m-d H:i'));

        UpsertModel::query()->upsert([
            'name' => 'a',
            'age' => 11,
            'weight' => 70,
        ], ['name', 'age']);

        // Assert is updated:
        $this->assertEquals(2, UpsertModel::query()->count());
        $upsert = UpsertModel::query()->get();
        $this->assertEquals(12, $upsert[0]->age);
        $this->assertEquals(11, $upsert[1]->age);
        $this->assertEquals(70, $upsert[1]->weight);

        UpsertModel::query()->upsert([
            'name' => 'a3',
            'age' => 11,
            'weight' => 70,
        ], ['name', 'age']);

        $this->assertEquals(3, UpsertModel::query()->count());

        UpsertModel::query()->upsert([
            'name' => 'a3',
            'age' => 14,
            'weight' => 70,
        ], ['name', 'age']);

        $this->assertEquals(4, UpsertModel::query()->count());

        FakeDB::dontMockQueryBuilder();
    }
}
