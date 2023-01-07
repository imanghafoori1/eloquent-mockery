<?php

namespace Imanghafoori\EloquentMockery\Tests\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class Mser extends Model
{
    public $fillable = ['username'];

    protected $table = 'users';

    public function city()
    {
        return $this->belongsToMany(Mity::class, 'pivot');
    }
}

class Mity extends Model
{
    protected $table = 'mities';

    public $fillable = ['name'];

    public function user()
    {
        return $this->belongsToMany(Mser::class, 'pivot');
    }
}

class ManyToManyTest extends TestCase
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
    public function belongs_to_many()
    {
        FakeDB::addRow('users', ['id' => 1, 'name' => 'Iman 1']);
        FakeDB::addRow('users', ['id' => 2, 'name' => 'Iman 2']);
        FakeDB::addRow('users', ['id' => 3, 'name' => 'Iman 3']);
        FakeDB::addRow('users', ['id' => 4, 'name' => 'Iman 4']);

        FakeDB::table('pivot')->addRow(['mity_id' => 1, 'mser_id' => 1]);
        FakeDB::table('pivot')->addRow(['mity_id' => 1, 'mser_id' => 2]);
        FakeDB::table('pivot')->addRow(['mity_id' => 2, 'mser_id' => 2]);
        FakeDB::table('pivot')->addRow(['mity_id' => 2, 'mser_id' => 3]);

        FakeDB::addRow('mities', ['id' => 1, 'name' => 'sss']);
        FakeDB::addRow('mities', ['id' => 2, 'name' => 'aaa']);
        FakeDB::addRow('mities', ['id' => 3, 'name' => 'bbb']);
        FakeDB::addRow('mities', ['id' => 4, 'name' => 'ccc']);
        FakeDB::addRow('mities', ['id' => 5, 'name' => 'ddd']);

        $user = Mser::where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->city);
        $this->assertEquals(1, $user->city->count());
        $this->assertEquals(1, $user->city[0]->id);

        $user = Mser::where('id', 2)->first();
        $this->assertEquals(2, $user->city->count());
        $this->assertEquals(2, $user->city()->count());
        $this->assertEquals(2, $user->city()->get()->count());
        $this->assertEquals(1, $user->city()->where('id', 1)->get()->count());
        $this->assertEquals(0, $user->city()->where('id', 9)->get()->count());
        $this->assertEquals(1, $user->city()->where('id', 1)->count());
        $this->assertEquals(1, $user->city()->first()->id);

        $this->assertEquals(1, $user->city[0]->id);
        $this->assertEquals(2, $user->city[1]->id);
        $this->assertEquals('sss', $user->city[0]->name);
        $this->assertEquals('aaa', $user->city[1]->name);

        $cities = $user->city()->get();
        $this->assertInstanceOf(Collection::class, $cities);
        $this->assertEquals(1, $user->city()->get(['id'])->first()->id);
        $this->assertEquals(1, $user->city()->get(['id as mid'])->first()->mid);
        $this->assertNull($user->city()->get(['id'])->first()->name);

        $user = Mser::with('city')->first();
        $this->assertEquals(1, $user->city->count());
    }
}
