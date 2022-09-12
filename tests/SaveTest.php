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
        unset($_SERVER['forTest']);
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

        SaveModel::saved(static function () {
            $_SERVER['saved'] = true;
        });
        SaveModel::updating(static function () {
            $_SERVER['updating'] = true;
        });
        SaveModel::updated(static function () {
            $_SERVER['updated'] = true;
        });
        SaveModel::saving(static function () {
            $_SERVER['saving'] = true;
        });

        SaveModel::creating(function () {
            $_SERVER['forTest']['creating'] = true;
        });
        SaveModel::created(function () {
            $_SERVER['forTest']['created'] = true;
        });

        $model = SaveModel::query()->find(1);
        $model->name = 'hello';

        $time = Carbon::now()->timestamp;
        $result = $model->save();
        $this->assertFalse($model->wasRecentlyCreated);

        $this->assertTrue($result);

        $this->assertTrue($_SERVER['forTest']['saved']);
        $this->assertTrue($_SERVER['forTest']['saving']);
        $this->assertTrue($_SERVER['forTest']['updated']);
        $this->assertTrue($_SERVER['forTest']['updating']);
        $this->assertTrue(! isset($_SERVER['forTest']['created']));
        $this->assertTrue(! isset($_SERVER['forTest']['creating']));

        $foo = SaveModel::getUpdatedModel();
        $this->assertEquals(1, $foo->id);
        $this->assertEquals('hello', $foo->name);

        $this->assertEquals($foo->updated_at->timestamp, $time);
        $this->assertTrue($foo->exists);
    }

    /**
     * @test
     */
    public function save_a_new_model()
    {
        SaveModel::setEventDispatcher(new Dispatcher());
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        SaveModel::saved(function () {
            $_SERVER['forTest']['saved'] = true;
        });
        SaveModel::saving(function () {
            $_SERVER['forTest']['saving'] = true;
        });

        SaveModel::creating(function () {
            $_SERVER['forTest']['creating'] = true;
        });
        SaveModel::created(function () {
            $_SERVER['forTest']['created'] = true;
        });

        SaveModel::updating(function () {
            $_SERVER['forTest']['updating'] = true;
        });
        SaveModel::updated(function () {
            $_SERVER['forTest']['updated'] = true;
        });

        $newModel = new SaveModel();
        $newModel->name = 'hello';
        $result = $newModel->save();

        $this->assertTrue($result);
        $this->assertTrue($newModel->wasRecentlyCreated);
        $this->assertTrue($newModel->exists);

        unset($_SERVER['saved'], $_SERVER['updating'], $_SERVER['updated'], $_SERVER['saving']);

        $this->assertTrue($_SERVER['forTest']['saved']);
        $this->assertTrue($_SERVER['forTest']['saving']);
        $this->assertTrue(! isset($_SERVER['forTest']['updated']));
        $this->assertTrue(! isset($_SERVER['forTest']['updating']));
        $this->assertTrue($_SERVER['forTest']['created']);
        $this->assertTrue($_SERVER['forTest']['creating']);

        $foo = SaveModel::getUpdatedModel();
        $this->assertEquals(null, $foo);

        $foo = SaveModel::getCreatedModel();
        $this->assertSame($foo, $newModel);
    }

    /**
     * @test
     */
    public function save_with_no_dispatcher()
    {
        SaveModel::unsetEventDispatcher();
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        $_SERVER['forTest']['saved'] = false;
        $_SERVER['forTest']['updating'] = false;
        $_SERVER['forTest']['updated'] = false;
        $_SERVER['forTest']['saving'] = false;

        SaveModel::saved(static function () {
            $_SERVER['saved'] = true;
        });
        SaveModel::updating(static function () {
            $_SERVER['updating'] = true;
        });
        SaveModel::updated(static function () {
            $_SERVER['updated'] = true;
        });
        SaveModel::saving(static function () {
            $_SERVER['saving'] = true;
        });

        $result = SaveModel::query()->find(1);
        $result->name = 'hello2';
        $time = Carbon::now()->timestamp;
        $result = $result->save();

        $this->assertTrue($result);

        $this->assertFalse($_SERVER['forTest']['saved']);
        $this->assertFalse($_SERVER['forTest']['saving']);
        $this->assertFalse($_SERVER['forTest']['updated']);
        $this->assertFalse($_SERVER['forTest']['updating']);

        $foo = SaveModel::getUpdatedModel();
        $this->assertEquals(1, $foo->id);
        $this->assertEquals('hello2', $foo->name);

        $this->assertEquals($foo->updated_at->timestamp, $time);
        $this->assertTrue($foo->exists);

        unset($_SERVER['saved'], $_SERVER['updating'], $_SERVER['updated'], $_SERVER['saving']);
    }

    /**
     * @test
     */
    public function updating_event_can_halt()
    {
        SaveModel::setEventDispatcher(new Dispatcher());
        SaveModel::addFakeRow(['id' => 1, 'name' => 'hi 1']);
        SaveModel::addFakeRow(['id' => 2, 'name' => 'hi 2']);

        SaveModel::updating(static function () {
            return false;
        });

        $_SERVER['updated'] = false;
        SaveModel::updated(static function () {
            $_SERVER['updated'] = true;
        });
        $_SERVER['forTest']['updated'] = false;
        SaveModel::updated(function () {
            $_SERVER['forTest']['updated'] = true;
        });

        $result = SaveModel::query()->find(1)->update(['name' => 'hello']);

        $this->assertFalse($result);
        $this->assertFalse($_SERVER['forTest']['updated']);

        $result = SaveModel::query()->find(1);
        $result->name = 'hello 3';

        $this->assertFalse($result->save());
        $this->assertFalse($_SERVER['forTest']['updated']);
    }
}
