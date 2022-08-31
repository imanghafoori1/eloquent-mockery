<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class SoftDeleteUser extends Model
{
    use SoftDeletes;
    use MockableModel;
}

class SoftDeleteTest extends TestCase
{
    public function tearDown(): void
    {
        SoftDeleteUser::stopFaking();
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
        $this->assertNotNull(SoftDeleteUser::getSoftDeletedModel(1));
        $this->assertNull(SoftDeleteUser::getSoftDeletedModel(2));

        $model = SoftDeleteUser::getSoftDeletedModel(0);
        $this->assertEquals(1, $model->id);
        $this->assertTrue($model->exists);

        $model = SoftDeleteUser::getSoftDeletedModel(1);
        $this->assertEquals(2, $model->id);
        $this->assertTrue($model->exists);

        // Can not be deleted twice.
        $count = SoftDeleteUser::destroy(1, 2);
        $this->assertEquals(0, $count);
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
        $user->delete(); // soft-deleted
        $this->assertEquals(Carbon::now()->getTimestamp(), $user->deleted_at->getTimestamp());

        $deletedModel = SoftDeleteUser::getSoftDeletedModel();
        $this->assertEquals($deletedModel->deleted_at, $user->deleted_at);
        $this->assertEquals($deletedModel->ff, $user->ff);

        $this->assertEquals(2, SoftDeleteUser::first()->id);
        $this->assertEquals(1, SoftDeleteUser::withTrashed()->first()->id);

        $this->assertNull(SoftDeleteUser::find(1));
        $this->assertNotNull(SoftDeleteUser::withTrashed()->find(1));
        $this->assertNotNull(SoftDeleteUser::withTrashed()->find(1)->deleted_at);

        $this->assertEquals(4, SoftDeleteUser::withTrashed()->count());
        $this->assertEquals(3, SoftDeleteUser::count());

        $this->assertEquals(4, SoftDeleteUser::withTrashed()->get()->count());
        $this->assertEquals(3, SoftDeleteUser::get()->count());

        $user->restore(); // restore the soft-deleted.
        $this->assertEquals(4, SoftDeleteUser::count());
        $this->assertNull(SoftDeleteUser::find(1)->deleted_at);
        $this->assertEquals(4, SoftDeleteUser::get()->count());
    }

    /**
     * @test
     */
    public function force_delete()
    {
        SoftDeleteUser::setEventDispatcher(new Dispatcher);

        SoftDeleteUser::addFakeRow(['id' => 1]);
        SoftDeleteUser::addFakeRow(['id' => 2]);
        $user = SoftDeleteUser::query()->find(1);

        $result = $user->forceDelete();
        $deletedModel = SoftDeleteUser::getDeletedModel();

        $this->assertEquals(1, $deletedModel->id);
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
        $user = SoftDeleteUser::query()->find(1);
        $this->assertNull($user);
        $user = SoftDeleteUser::query()->find(2);
        $this->assertNotNull($user);
    }
}
