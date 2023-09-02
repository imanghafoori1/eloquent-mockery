<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class CreatyModel extends Model
{
    protected $fillable = ['name'];

    protected $table = 'users';
}

class CreateTest extends TestCase
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
    public function create()
    {
        Carbon::setTestNow(Carbon::create(2020));
        CreatyModel::reguard();
        CreatyModel::setEventDispatcher(new Dispatcher());
        FakeDB::addRow('users', ['id' => 1]);
        FakeDB::addRow('users', ['id' => 2]);

        CreatyModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        CreatyModel::creating(function () {
            $_SERVER['creating'] = true;
        });
        CreatyModel::created(function () {
            $_SERVER['created'] = true;
        });
        CreatyModel::saving(function () {
            $_SERVER['saving'] = true;
        });
        $bar = CreatyModel::query()->create([
            'id' => 12,
            'name' => 'hello',
            'family' => 'gha',
        ]);

        $this->assertTrue($_SERVER['saved']);
        $this->assertTrue($_SERVER['saving']);
        $this->assertTrue($_SERVER['created']);
        $this->assertTrue($_SERVER['creating']);

        $model = CreatyModel::query()->find(3);
        $this->assertEquals('hello', $model->name);
        $this->assertEquals(3, $model->id);
        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);

        $this->assertEquals([
            'name' => 'hello',
            'updated_at' => '2020-01-01 00:00:00',
            'created_at' => '2020-01-01 00:00:00',
            'id' => 3,
        ], FakeDB::getLatestRow('users'));

        unset($_SERVER['saved']);
        unset($_SERVER['saving']);
        unset($_SERVER['created']);
        unset($_SERVER['creating']);
    }
}
