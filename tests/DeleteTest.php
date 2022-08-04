<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class DeleteUser extends Model
{
    use MockableModel;
}

class SoftDeleteUser extends Model
{
    use SoftDeletes;
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

        $count = DeleteUser::destroy(1, 2);
        $this->assertEquals(2, $count);
        $this->assertEquals(2, count(DeleteUser::$deletedModels));

        $model = DeleteUser::getDeletedModel(0);
        $this->assertEquals($model->id, 1);
        $this->assertFalse($model->exists);

        // Can not be deleted twice.
        $count = DeleteUser::destroy(1, 2);
        $this->assertEquals(0, $count);

        $this->assertNull(DeleteUser::find(1));
        $this->assertNotNull(DeleteUser::find(3));
        $this->assertEquals(2, DeleteUser::count());
    }

    /**
     * @test
     */
    public function destroy_soft_delete()
    {
        SoftDeleteUser::setEventDispatcher(new Dispatcher());
        SoftDeleteUser::addFakeRow(['id' => 1]);
        SoftDeleteUser::addFakeRow(['id' => 2]);
        SoftDeleteUser::addFakeRow(['id' => 3]);
        SoftDeleteUser::addFakeRow(['id' => 4]);
        SoftDeleteUser::fakeSoftDelete();

        $count = SoftDeleteUser::destroy(1, 2);
        $this->assertEquals(2, $count);
        $this->assertEquals(2, count(SoftDeleteUser::$softDeletedModels));

        $model = SoftDeleteUser::getSoftDeletedModel(0);
        $this->assertEquals($model->id, 1);
        $this->assertTrue($model->exists);

        // Can not be deleted twice.
        $count = SoftDeleteUser::destroy(1, 2);
        $this->assertEquals(0, $count);
        //
        $this->assertNull(SoftDeleteUser::find(1));
        $this->assertNotNull(SoftDeleteUser::find(3));
        $this->assertEquals(2, SoftDeleteUser::count());
    }

    /**
     * @test
     */
    public function soft_delete()
    {
        SoftDeleteUser::setEventDispatcher(new Dispatcher());
        SoftDeleteUser::addFakeRow(['id' => 1]);
        SoftDeleteUser::addFakeRow(['id' => 2]);
        SoftDeleteUser::addFakeRow(['id' => 3]);
        SoftDeleteUser::addFakeRow(['id' => 4]);
        SoftDeleteUser::fakeSoftDelete();
        $user = SoftDeleteUser::find(1);
        $this->assertNull($user->deleted_at);
        $user->delete();
        $this->assertNotNull($user->deleted_at);

        $deletedModel = SoftDeleteUser::getSoftDeletedModel();
        $this->assertEquals($deletedModel->deleted_at, $user->deleted_at);
        $this->assertEquals($deletedModel->ff, $user->ff);
        $this->assertNull(SoftDeleteUser::find(1));
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

        $this->assertNull(DeleteUser::find(1));
        $this->assertEquals(0, DeleteUser::count());
    }

    /**
     * @test
     */
    public function delete_non_existent()
    {
        DeleteUser::addFakeRow(['id' => 1]);
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
        $std->deleted = false;
        DeleteUser::deleting(function () use ($std) {
            $std->deleting = true;
            return false;
        });

        DeleteUser::deleted(function () use ($std) {
            $std->deleted = true;
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
        $this->assertFalse($std->deleted);
    }

    /**
     * @test
     */
    public function delete_events()
    {
        DeleteUser::fakeDelete();
        DeleteUser::setEventDispatcher(new Dispatcher);
        $std = new \stdClass();
        $std->deleting = false;
        $std->deleted = false;
        DeleteUser::deleting(function () use ($std) {
            $std->deleting = true;
        });

        DeleteUser::deleted(function () use ($std) {
            $std->deleted = true;
        });

        $user = new DeleteUser();
        $user->id = 1;
        $user->exists = true;
        $result = $user->delete();
        $model = DeleteUser::getDeletedModel();

        $this->assertNull($model);
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
        $this->assertTrue($std->deleting);
        $this->assertTrue($std->deleted);
    }

    /**
     * @test
     */
    public function force_delete()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);

        DeleteUser::addFakeRow(['id' => 1]);
        $user = DeleteUser::query()->find(1);

        $result = $user->forceDelete();
        $deletedModel = DeleteUser::getDeletedModel();

        $this->assertEquals($deletedModel->id, 1);
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
        $deletedModel = DeleteUser::getDeletedModel();

        $this->assertEquals($deletedModel->id, 1);
        $this->assertSame($deletedModel, $user);
        $this->assertTrue($result);
        $this->assertFalse($deletedModel->exists);
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