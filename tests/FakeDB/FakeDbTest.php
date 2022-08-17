<?php

namespace Imanghafoori\EloquentMockery\Tests\FakeDB;

use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class FakeDbUser extends Model
{
    use MockableModel;

    public $table = 'users';
}

class FakeDbTest extends TestCase
{
    /**
     * @test
     */
    public function fake_row()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $users = FakeDbUser::get();
        $this->assertEquals(2, $users->count());

        FakeDB::truncate();
    }

    /**
     * @test
     */
    public function fake_ro3w()
    {
        FakeDB::table('users')->addRow(['id' => 1, 'username' => 'Iman']);
        FakeDB::table('users')->addRow(['id' => 2, 'username' => 'Ghafoori']);

        $users = FakeDbUser::from('sdfjf')->get();
        $this->assertEquals(0, $users->count());

        $users = FakeDbUser::from('users')->get();
        $this->assertEquals(2, $users->count());

        $users = FakeDbUser::from('users')->where('id', 1)->get();
        $this->assertEquals(1, $users->count());
    }
}