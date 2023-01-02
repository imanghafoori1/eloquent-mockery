<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class UpdateyModel extends Model
{
    protected $fillable = ['name'];

    use MockableModel;
}

class UpdateTest extends TestCase
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
    public function calling_update_method_on_model_object()
    {
        UpdateyModel::setEventDispatcher(new Dispatcher());
        UpdateyModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        UpdateyModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        UpdateyModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        UpdateyModel::updating(function () {
            $_SERVER['updating'] = true;
        });
        UpdateyModel::updated(function () {
            $_SERVER['updated'] = true;
        });
        UpdateyModel::saving(function () {
            $_SERVER['saving'] = true;
        });
        $time = Carbon::now()->timestamp;
        $result = UpdateyModel::query()->find(1)->update(['name' => 'hello']);

        $this->assertTrue($result);

        $this->assertTrue($_SERVER['saved']);
        $this->assertTrue($_SERVER['saving']);
        $this->assertTrue($_SERVER['updated']);
        $this->assertTrue($_SERVER['updating']);

        $foo = UpdateyModel::getUpdatedModel();
        //$this->assertEquals(1, $foo->id);
        //$this->assertEquals('hello', $foo->name);

        //$this->assertEquals($foo->updated_at->timestamp, $time);
        //$this->assertTrue($foo->exists);

        $this->assertNull(UpdateyModel::getUpdatedModel(1));
        //$this->assertSame(UpdateyModel::getUpdatedModel(), UpdateyModel::getSavedModel());

        unset($_SERVER['saved']);
        unset($_SERVER['updating']);
        unset($_SERVER['updated']);
        unset($_SERVER['saving']);
    }

    /**
     * @test
     */
    public function update_with_no_dispatcher()
    {
        UpdateyModel::unsetEventDispatcher();
        UpdateyModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        UpdateyModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        $_SERVER['saved'] = false;
        $_SERVER['updating'] = false;
        $_SERVER['updated'] = false;
        $_SERVER['saving'] = false;

        UpdateyModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        UpdateyModel::updating(function () {
            $_SERVER['updating'] = true;
        });
        UpdateyModel::updated(function () {
            $_SERVER['updated'] = true;
        });
        UpdateyModel::saving(function () {
            $_SERVER['saving'] = true;
        });
        $time = Carbon::now()->timestamp;
        $result = UpdateyModel::query()->find(1)->update(['name' => 'hello']);

        $this->assertTrue($result);

        $this->assertFalse($_SERVER['saved']);
        $this->assertFalse($_SERVER['saving']);
        $this->assertFalse($_SERVER['updated']);
        $this->assertFalse($_SERVER['updating']);

        $foo = UpdateyModel::getUpdatedModel(0);
        //$this->assertEquals(1, $foo->id);
        //$this->assertEquals('hello', $foo->name);

        //$this->assertEquals($foo->updated_at->timestamp, $time);
        //$this->assertTrue($foo->exists);

        //$this->assertNull(UpdateyModel::getUpdatedModel(1));

        unset($_SERVER['saved']);
        unset($_SERVER['updating']);
        unset($_SERVER['updated']);
        unset($_SERVER['saving']);
    }

    /**
     * @test
     */
    public function updating_event()
    {
        UpdateyModel::setEventDispatcher(new Dispatcher());
        UpdateyModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        UpdateyModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        UpdateyModel::updating(function () {
            return false;
        });
        $_SERVER['updated'] = false;
        UpdateyModel::updated(function () {
            $_SERVER['updated'] = true;
        });

        $result = UpdateyModel::query()->find(1)->update(['name' => 'hello']);

        $this->assertFalse($result);
        $this->assertFalse($_SERVER['updated']);

        $model = UpdateyModel::query()->find(1);
        $this->assertEquals('hi 1', $model->name);
    }

    /**
     * @test
     */
    public function update_raw()
    {
        UpdateyModel::setEventDispatcher(new Dispatcher());
        UpdateyModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        UpdateyModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        $_SERVER['saved'] = false;
        $_SERVER['updating'] = false;
        $_SERVER['updated'] = false;
        $_SERVER['saving'] = false;

        UpdateyModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        UpdateyModel::updating(function () {
            $_SERVER['updating'] = true;
        });
        UpdateyModel::updated(function () {
            $_SERVER['updated'] = true;
        });
        UpdateyModel::saving(function () {
            $_SERVER['saving'] = true;
        });

        $result = UpdateyModel::query()->whereIn('id', [1, 2])->update(['name' => 'hello']);

        $this->assertEquals(2, $result);

        $this->assertFalse($_SERVER['saved']);
        $this->assertFalse($_SERVER['saving']);
        $this->assertFalse($_SERVER['updated']);
        $this->assertFalse($_SERVER['updating']);

        $foo = UpdateyModel::getUpdatedModel();
        $this->assertNull($foo);

        // No event has been fired.
        unset($_SERVER['saved']);
        unset($_SERVER['updating']);
        unset($_SERVER['updated']);
        unset($_SERVER['saving']);

        // The rows are changed.
        $model = UpdateyModel::query()->find(1);
        $this->assertEquals('hello', $model->name);
        $this->assertEquals(Carbon::now()->getTimestamp(), $model->updated_at->getTimestamp());

        $model = UpdateyModel::query()->find(2);
        $this->assertEquals('hello', $model->name);
        $this->assertEquals(Carbon::now()->getTimestamp(), $model->updated_at->getTimestamp());
    }
}
