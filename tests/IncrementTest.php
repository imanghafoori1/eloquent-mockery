<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class IncrementUser extends Model
{
    //
}

class IncrementTest extends TestCase
{
    public function test_increment()
    {
        FakeDB::mockQueryBuilder();
        Carbon::setTestNow('2022-10-23 01:01:01');
        $table = (new IncrementUser())->getTable();
        FakeDB::addRow($table, [
            'id' => 1,
            'age' => 19,
            'name' => 'Iman',
        ]);

        FakeDB::addRow($table, [
            'id' => 2,
            'age' => 19,
            'name' => 'Ghafoori',
        ]);

        IncrementUser::resolveConnection()->enableQueryLog();
        IncrementUser::query()->where('id', 1)->increment('age', 1, ['name' => 'No Name']);
        $log = (IncrementUser::resolveConnection()->getQueryLog())[0];

        $this->assertEquals('update "increment_users" set "age" = "age" + 1, "name" = ?, "increment_users"."updated_at" = ? where "id" = ?', $log['query']);
        $this->assertEquals(["No Name", "2022-10-23 01:01:01", 1], $log['bindings']);
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(20, $user->age);
        $this->assertEquals('No Name', $user->name);
        $this->assertEquals('2022-10-23 01:01:01', (string) $user->updated_at);

        IncrementUser::query()->where('id', 1)->increment('age', 10);
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(30, $user->age);

        $user = IncrementUser::query()->find(2);
        $this->assertEquals(19, $user->age);

        FakeDB::truncate();
        FakeDB::dontMockQueryBuilder();
    }

    public function test_decrement()
    {
        Carbon::setTestNow('2022-10-23 01:01:01');
        FakeDB::mockQueryBuilder();
        $table = (new IncrementUser())->getTable();

        FakeDB::addRow($table, [
            'id' => 1,
            'age' => 19,
            'name' => 'Iman',
        ]);
        FakeDB::addRow($table, [
            'id' => 2,
            'age' => 19,
            'name' => 'Ghafoori',
        ]);

        IncrementUser::resolveConnection()->enableQueryLog();
        IncrementUser::query()->where('id', 1)->decrement('age');
        $log = IncrementUser::resolveConnection()->getQueryLog();

        $this->assertCount(1, $log);
        $query = 'update "increment_users" set "age" = "age" - 1, "increment_users"."updated_at" = ? where "id" = ?';
        $this->assertEquals($query, $log[0]['query']);

        $user = IncrementUser::query()->find(1);
        $this->assertEquals(18, $user->age);

        IncrementUser::query()->where('id', 1)->decrement('age', 10, ['name' => 'i_m_a_n']);
        $user = IncrementUser::query()->find(1);
        $this->assertEquals(8, $user->age);
        $this->assertEquals('i_m_a_n', $user->name);

        $user = IncrementUser::query()->find(2);
        $this->assertEquals(19, $user->age);

        FakeDB::truncate();
        FakeDB::dontMockQueryBuilder();
    }

}
