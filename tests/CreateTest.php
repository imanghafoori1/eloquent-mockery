<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class CreatyModel extends Model
{
    protected $fillable = ['name'];

    use MockableModel;
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
        CreatyModel::reguard();
        CreatyModel::setEventDispatcher(new Dispatcher());
        CreatyModel::addFakeRow(['id' => 1]);
        CreatyModel::addFakeRow(['id' => 2]);

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

        $foo = CreatyModel::getCreatedModel();
        $this->assertSame($foo, $bar);
        $this->assertTrue($_SERVER['saved']);
        $this->assertTrue($_SERVER['saving']);
        $this->assertTrue($_SERVER['created']);
        $this->assertTrue($_SERVER['creating']);

        $this->assertEquals(3, $foo->id);
        $this->assertEquals('hello', $foo->name);
        $this->assertNull($foo->family);
        $this->assertNotNull($foo->created_at);
        $this->assertNotNull($foo->updated_at);
        $this->assertTrue($foo->exists);
        $this->assertTrue($foo->wasRecentlyCreated);

        $this->assertSame(CreatyModel::getSavedModel(), CreatyModel::getCreatedModel());
        $this->assertNull(CreatyModel::getCreatedModel(1));
        $this->assertNull(CreatyModel::getSavedModel(1));
        $model = CreatyModel::query()->find(3);
        $this->assertEquals('hello', $model->name);
        $this->assertEquals(3, $model->id);
        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);

        unset($_SERVER['saved']);
        unset($_SERVER['saving']);
        unset($_SERVER['created']);
        unset($_SERVER['creating']);
    }
}
