<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class SaveModel extends Model
{
    protected $fillable = ['name'];

    use MockableModel;
}

class SaveTest extends TestCase
{
    public function tearDown(): void
    {
        SaveModel::stopFaking();
    }

    /**
     * @test
     */
    public function save()
    {
        SaveModel::setEventDispatcher(new Dispatcher());
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        SaveModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        SaveModel::updating(function () {
            $_SERVER['updating'] = true;
        });
        SaveModel::updated(function () {
            $_SERVER['updated'] = true;
        });
        SaveModel::saving(function () {
            $_SERVER['saving'] = true;
        });

        $result = SaveModel::query()->find(1);
        $result->name = 'hello';
        $result = $result->save();

        $this->assertTrue($result);

        $this->assertTrue($_SERVER['saved']);
        $this->assertTrue($_SERVER['saving']);
        $this->assertTrue($_SERVER['updated']);
        $this->assertTrue($_SERVER['updating']);

        $foo = SaveModel::$updatedModels[0];
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
    public function save_with_no_dispatcher()
    {
        SaveModel::unsetEventDispatcher();
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        $_SERVER['saved'] = false;
        $_SERVER['updating'] = false;
        $_SERVER['updated'] = false;
        $_SERVER['saving'] = false;

        SaveModel::saved(function () {
            $_SERVER['saved'] = true;
        });
        SaveModel::updating(function () {
            $_SERVER['updating'] = true;
        });
        SaveModel::updated(function () {
            $_SERVER['updated'] = true;
        });
        SaveModel::saving(function () {
            $_SERVER['saving'] = true;
        });

        $result = SaveModel::query()->find(1);
        $result->name = 'hello2';
        $result = $result->save();

        $this->assertTrue($result);

        $this->assertFalse($_SERVER['saved']);
        $this->assertFalse($_SERVER['saving']);
        $this->assertFalse($_SERVER['updated']);
        $this->assertFalse($_SERVER['updating']);

        $foo = SaveModel::$updatedModels[0];
        $this->assertEquals(1, $foo->id);
        $this->assertEquals('hello2', $foo->name);

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
    public function saving_event()
    {
        SaveModel::setEventDispatcher(new Dispatcher());
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        SaveModel::updating(function () {
            return false;
        });
        $_SERVER['updated'] = false;
        SaveModel::updated(function () {
            $_SERVER['updated'] = true;
        });

        $result = SaveModel::query()->find(1)->update(['name' => 'hello']);

        $this->assertFalse($result);
        $this->assertFalse($_SERVER['updated']);

        $result = SaveModel::query()->find(1);
        $result->name = 'hello 3';

        $this->assertFalse($result->save());
    }
}
