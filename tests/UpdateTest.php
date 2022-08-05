<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
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
        UpdateyModel::stopFaking();
    }

    /**
     * @test
     */
    public function update()
    {
        UpdateyModel::setEventDispatcher(new Dispatcher());
        UpdateyModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        UpdateyModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);
        UpdateyModel::fakeUpdate();

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
        $result = UpdateyModel::query()
            ->find(1)
            ->update(['name' => 'hello']);

        $this->assertTrue($result);

        $this->assertTrue($_SERVER['saved']);
        $this->assertTrue($_SERVER['saving']);
        $this->assertTrue($_SERVER['updated']);
        $this->assertTrue($_SERVER['updating']);

        $foo = UpdateyModel::$updatedModels[0];
        $this->assertEquals(1, $foo->id);
        $this->assertEquals('hello', $foo->name);

        $this->assertEquals($foo->updated_at->timestamp, Carbon::now()->timestamp);
        $this->assertTrue($foo->exists);

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
        UpdateyModel::fakeUpdate();

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
        UpdateyModel::fakeUpdate();

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

        $result = UpdateyModel::query()
            ->whereIn('id', [1, 2])
            ->update(['name' => 'hello']);

        $this->assertEquals(2, $result);

        $this->assertFalse($_SERVER['saved']);
        $this->assertFalse($_SERVER['saving']);
        $this->assertFalse($_SERVER['updated']);
        $this->assertFalse($_SERVER['updating']);

        $foo = UpdateyModel::$updatedModels;
        $this->assertEmpty($foo);

        unset($_SERVER['saved']);
        unset($_SERVER['updating']);
        unset($_SERVER['updated']);
        unset($_SERVER['saving']);

        $model = UpdateyModel::query()->find(1);
        // todo: it should return updated row.
        $this->assertEquals('hi 1', $model->name);
    }
}
