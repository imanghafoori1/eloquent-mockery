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
    /**
     * @test
     */
    public function delete()
    {
        DeleteUser::fakeDelete();

        $user = new DeleteUser();
        $user->id = 1;
        $user->exists = true;
        $result = $user->delete();

        $user2 = DeleteUser::getDeletedModel();

        $this->assertEquals(spl_object_hash($user), spl_object_hash($user2));
        $this->assertTrue($result);
        $this->assertFalse($user->exists);

        DeleteUser::stopFaking();
    }

    /**
     * @test
     */
    public function delete_non_existent()
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
        $user->exists = false;
        $result = $user->delete();

        $model = DeleteUser::getDeletedModel();

        $this->assertNull($model);
        $this->assertNull($result);
        $this->assertFalse($user->exists);
        $this->assertFalse($std->deleting);

        DeleteUser::stopFaking();
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

        DeleteUser::stopFaking();
    }

    /**
     * @test
     */
    public function force_delete()
    {
        DeleteUser::fakeDelete();
        DeleteUser::setEventDispatcher(new Dispatcher);

        $user = new DeleteUser();
        $user->id = 1;
        $user->exists = true;
        $result = $user->forceDelete();
        $model = DeleteUser::getDeletedModel();

        $this->assertEquals($model->id, 1);
        $this->assertTrue($result);
        $this->assertFalse($user->exists);

        DeleteUser::stopFaking();
    }
}