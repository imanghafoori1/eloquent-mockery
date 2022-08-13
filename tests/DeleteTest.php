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

        $count = DeleteUser::destroy(1, 2);
        $this->assertEquals(2, $count);
        $this->assertCount(2, DeleteUser::$changedModels['deleted']);

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
        DeleteUser::deleting(static function () use ($std) {
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
        DeleteUser::fake();
        DeleteUser::setEventDispatcher(new Dispatcher);
        DeleteUser::addFakeRow([
            'id' => 1,
            'name' => 'mocky'
        ]);
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

        $user = DeleteUser::first();
        $result = $user->delete();
        $model = DeleteUser::getDeletedModel();

        $this->assertNull($model);
        $this->assertFalse($result);
        $this->assertTrue($user->exists);
        $this->assertTrue($std->deleting);
        $this->assertFalse($std->deleted);
        $this->assertNotNull(DeleteUser::first());
    }

    /**
     * @test
     */
    public function delete_events()
    {
        DeleteUser::fake();
        DeleteUser::setEventDispatcher(new Dispatcher);
        $std = new \stdClass();
        $std->deleting = false;
        $std->deleted = false;
        DeleteUser::deleting(static function () use ($std) {
            $std->deleting = true;
        });

        DeleteUser::deleted(static function () use ($std) {
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

        $this->assertEquals(1, $deletedModel->id);
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

        $this->assertEquals(1, $deletedModel->id);
        $this->assertSame($deletedModel, $user);
        $this->assertTrue($result);
        $this->assertFalse($deletedModel->exists);
    }

    /**
     * @test
     */
    public function raw_delete()
    {
        DeleteUser::fake();
        DeleteUser::setEventDispatcher(new Dispatcher);

        $count = DeleteUser::where('id', 1)->delete();
        $model = DeleteUser::getDeletedModel();

        $this->assertEquals(0, $count);
        $this->assertNull($model);
    }
}
