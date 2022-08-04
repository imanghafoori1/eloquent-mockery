<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class DeleteUser extends Model
{
    use MockableModel;
}

class DeleteTest extends TestCase
{
    public function tearDown(): void
    {
        DeleteUser::stopFaking();
    }

    /**
     * @test
     */
    public function destroy()
    {
        DeleteUser::addFakeRow(['id' => 1]);
        DeleteUser::addFakeRow(['id' => 2]);
        DeleteUser::addFakeRow(['id' => 3]);
        DeleteUser::addFakeRow(['id' => 4]);

        $result = DeleteUser::destroy(1, 2);
        $this->assertEquals(2, $result);
        $this->assertEquals(2, count(DeleteUser::$deletedModels));

        $model = DeleteUser::getDeletedModel(0);
        $this->assertEquals($model->id, 1);
        $this->assertFalse($model->exists);

        $result = DeleteUser::destroy(1, 2);
        $this->assertEquals(0, $result);

        $this->assertNull(DeleteUser::find(1));
        $this->assertEquals(2, DeleteUser::count());
    }

    /**
     * @test
     */
    public function delete()
    {
        DeleteUser::addFakeRow(['id' => 1]);
        $user = DeleteUser::find(1);
        $result = $user->delete();

        $user2 = DeleteUser::getDeletedModel();

        $this->assertEquals(spl_object_hash($user), spl_object_hash($user2));
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
    }

    /**
     * @test
     */
    public function delete_non_existent()
    {
        DeleteUser::addFakeRow(['id' => 1]);
        DeleteUser::fakeDelete();
        DeleteUser::setEventDispatcher(new Dispatcher);

        $std = new \stdClass();
        $std->deleting = false;
        DeleteUser::deleting(function () use ($std) {
            $std->deleting = true;
            return false;
        });

        $user = new DeleteUser();
        $user->id = 1;
        $user->exists = false;
        $result = $user->delete();

        $model = DeleteUser::getDeletedModel();

        $this->assertNull($model);
        $this->assertNull($result);
        $this->assertFalse($user->exists);
        $this->assertFalse($std->deleting);
    }

    /**
     * @test
     */
    public function delete_on_deleting()
    {
        DeleteUser::fakeDelete();
        DeleteUser::setEventDispatcher(new Dispatcher);
        $std = new \stdClass();
        $std->deleting = false;
        DeleteUser::deleting(function () use ($std) {
            $std->deleting = true;
            return false;
        });

        $user = new DeleteUser();
        $user->id = 1;
        $user->exists = true;
        $result = $user->delete();
        $model = DeleteUser::getDeletedModel();

        $this->assertNull($model);
        $this->assertFalse($result);
        $this->assertTrue($user->exists);
        $this->assertTrue($std->deleting);
    }

    /**
     * @test
     */
    public function force_delete()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);

        DeleteUser::addFakeRow(['id' => 1]);
        $user = DeleteUser::find(1);

        $result = $user->forceDelete();
        $model = DeleteUser::getDeletedModel();

        $this->assertEquals($model->id, 1);
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
    }

    /**
     * @test
     */
    public function delete_or_fail()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);
        DeleteUser::addFakeRow(['id' => 1]);
        $user = DeleteUser::find(1);

        $result = $user->deleteOrFail();
        $model = DeleteUser::getDeletedModel();

        $this->assertEquals($model->id, 1);
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
    }

    /**
     * @test
     */
    public function raw_delete()
    {
        DeleteUser::fakeDelete();
        DeleteUser::setEventDispatcher(new Dispatcher);

        $count = DeleteUser::where('id', 1)->delete();
        $model = DeleteUser::getDeletedModel();

        $this->assertEquals(0, $count);
        $this->assertNull($model);
    }
}