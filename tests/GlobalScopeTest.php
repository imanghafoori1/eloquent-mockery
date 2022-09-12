<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class GlobalScopeUser extends Model
{
    use MockableModel;

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
        GlobalScopeUser::stopFaking();
    }

    /**
     * @test
     */
    public function global_scope_test()
    {
        GlobalScopeUser::addGlobalScope(new AncientScope);
        GlobalScopeUser::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20,]);
        GlobalScopeUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30,]);
        GlobalScopeUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34,]);

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
        GlobalScopeUser::addFakeRow(['id' => 1, 'name' => 'Iman 1', 'age' => 20]);
        GlobalScopeUser::addFakeRow(['id' => 2, 'name' => 'Iman 2', 'age' => 30]);
        GlobalScopeUser::addFakeRow(['id' => 3, 'name' => 'Iman 3', 'age' => 34]);
        GlobalScopeUser::addFakeRow(['id' => 4, 'name' => 'Iman 4', 'age' => 37]);
        GlobalScopeUser::addFakeRow(['id' => 5, 'name' => 'Iman 5', 'age' => 40]);

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
