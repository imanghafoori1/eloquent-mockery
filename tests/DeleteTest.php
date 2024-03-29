<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class DeleteUser extends Model
{
    protected $table = 'users';
}

class DeleteTest extends TestCase
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
    public function destroy()
    {
        FakeDB::addRow('users', ['id' => 1, 'username' => 'hello1']);
        FakeDB::addRow('users', ['id' => 2, 'username' => 'hello2']);
        FakeDB::addRow('users', ['id' => 3, 'username' => 'hello3']);
        FakeDB::addRow('users', ['id' => 4, 'username' => 'hello4']);
        //DeleteUser::fakeSoftDelete();
        $count = DeleteUser::destroy(1, 2);
        $this->assertEquals(2, $count);

        // Can not be deleted twice.
        $count = DeleteUser::destroy(1, 2);
        $this->assertEquals(0, $count);

        $this->assertNull(DeleteUser::find(1));
        $this->assertNotNull(DeleteUser::find(3));
        $this->assertEquals(2, DeleteUser::count());
        $deletedRows = FakeDB::$deletedRows['users'];
        // we have 2 delete queries:
        $this->assertCount(2, $deletedRows);
        // each query has deleted 1 row:
        $this->assertCount(1, $deletedRows[0]);
        $this->assertCount(1, $deletedRows[1]);
        $this->assertEquals([
            'id' => 1,
            'username' => 'hello1',
        ], $deletedRows[0][0]);

        $this->assertEquals([
            'id' => 2,
            'username' => 'hello2',
        ], $deletedRows[1][0]);
    }

    /**
     * @test
     */
    public function delete()
    {
        FakeDB::addRow('users', ['id' => 1]);
        $user = DeleteUser::find(1);
        $result = $user->delete();

        //$user2 = DeleteUser::getDeletedModel();

        //$this->assertEquals(spl_object_hash($user), spl_object_hash($user2));
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
        FakeDB::addRow('users', ['id' => 1]);
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

        //$model = DeleteUser::getDeletedModel();

        //$this->assertNull($model);
        $this->assertNull($result);
        $this->assertFalse($user->exists);
        $this->assertFalse($std->deleting);
    }

    /**
     * @test
     */
    public function delete_on_deleting()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'mocky',
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
        //$model = DeleteUser::getDeletedModel();

        //$this->assertNull($model);
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
        DeleteUser::setEventDispatcher(new Dispatcher);
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'mocky',
        ]);
        $std = new \stdClass();
        $std->deleting = false;
        $std->deleted = false;
        DeleteUser::deleting(function () use ($std) {
            $std->deleting = true;
        });

        DeleteUser::deleted(function () use ($std) {
            $std->deleted = true;
        });

        $user = DeleteUser::find(1);
        $result = $user->delete();

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

        FakeDB::addRow('users', ['id' => 3, 'username' => 'hello']);
        $user = DeleteUser::query()->find(3);

        $result = $user->forceDelete();
        $this->assertTrue($result);
        $this->assertFalse($user->exists);
        $row = ['id' => 3, 'username' => 'hello'];
        $this->assertEquals([[$row]], FakeDB::$deletedRows['users']);
    }

    /**
     * @test
     */
    public function delete_or_fail()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);
        FakeDB::addRow('users', ['id' => 1, 'username' => 'hello']);
        $user = DeleteUser::find(1);

        if (! method_exists($user, 'deleteOrFail')) {
            $this->markTestSkipped();
        }
        $result = $user->deleteOrFail();

        $this->assertTrue($result);
        $this->assertEquals([
            [['id' => 1, 'username' => 'hello']]
        ], FakeDB::$deletedRows['users']);
    }

    /**
     * @test
     */
    public function raw_delete_empty_table()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);

        $count = DeleteUser::where('id', 1)->delete();
        $this->assertEquals(0, $count);

        $this->assertCount(1, FakeDB::$deletedRows['users']);
        $this->assertEmpty(FakeDB::$deletedRows['users'][0]);
    }

    /**
     * @test
     */
    public function raw_delete2()
    {
        DeleteUser::setEventDispatcher(new Dispatcher);
        FakeDB::addRow('users', [
            'id' => 1,
            'name' => 'mocky',
        ]);
        $count = DeleteUser::where('id', 2)->delete();

        $this->assertEquals(0, $count);
        $this->assertCount(1, FakeDB::$deletedRows['users']);
        $this->assertEquals([], FakeDB::$deletedRows['users'][0]);
    }
}
