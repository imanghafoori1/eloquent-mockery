<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class GlobalScopeUser extends Model
{
    protected $table = 'users';

    public function scopeIman($query)
    {
        $query->where('name', 'Iman 3');
    }
}

class AncientScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('id', '>', 2);
    }
}

class GlobalScopeTest extends TestCase
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
    public function global_scope_test()
    {
        GlobalScopeUser::addGlobalScope(new AncientScope);
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

        $user = GlobalScopeUser::get();
        $user = $user[0];
        $this->assertEquals('Iman 3', $user->name);
        $user = GlobalScopeUser::query()->get();
        $user = $user[0];
        $this->assertEquals('Iman 3', $user->name);

        GlobalScopeUser::clearBootedModels();
    }

    /**
     * @test
     */
    public function global_scope_raw_update()
    {
        GlobalScopeUser::addGlobalScope(new AncientScope);
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1', 'age' => 20]);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2', 'age' => 30]);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3', 'age' => 34]);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4', 'age' => 37]);
        FakeDB::addRow('users', ['id' => 5, 'name' => 'Iman 5', 'age' => 40]);

        $count = GlobalScopeUser::where('age', 40)->count();
        $this->assertEquals(1, $count);

        $count = GlobalScopeUser::query()->update(['age' => 40]);
        $this->assertEquals(3, $count);

        $count = GlobalScopeUser::where('age', 40)->count();
        $this->assertEquals(3, $count);

        $count = GlobalScopeUser::query()->withoutGlobalScopes()->update(['age' => 40]);
        $this->assertEquals(5, $count);

        $count = GlobalScopeUser::iman()->withoutGlobalScopes()->count();
        $this->assertEquals(1, $count);

        $count = GlobalScopeUser::where('age', 40)->count();
        $this->assertEquals(3, $count);

        $count = GlobalScopeUser::withoutGlobalScopes()->count();
        $this->assertEquals(5, $count);

        GlobalScopeUser::clearBootedModels();
    }
}
