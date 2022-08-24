<?php

namespace Imanghafoori\EloquentMockery\Tests;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Imanghafoori\EloquentMockery\FakeDB;
use Imanghafoori\EloquentMockery\MockableModel;
use PHPUnit\Framework\TestCase;

class Mser extends Model
{
    public $fillable = ['username'];

    use MockableModel;

    public function city()
    {
        return $this->belongsToMany(Mity::class, 'pivot');
    }
}

class Mity extends Model
{
    use MockableModel;

    public $fillable = ['name'];

    public function user()
    {
        return $this->belongsToMany(Mser::class, 'pivot');
    }
}

class ManyToManyTest extends TestCase
{
    /**
     * @test_
     */
    public function belongs_to_many()
    {
        Mser::addFakeRow(['id' => 1, 'name' => 'Iman 1']);
        Mser::addFakeRow(['id' => 2, 'name' => 'Iman 2']);
        Mser::addFakeRow(['id' => 3, 'name' => 'Iman 3']);
        Mser::addFakeRow(['id' => 4, 'name' => 'Iman 4']);

        FakeDB::table('pivot')->addRow(['mity_id' => 1, 'mser_id' => 1]);
        FakeDB::table('pivot')->addRow(['mity_id' => 1, 'mser_id' => 2]);
        FakeDB::table('pivot')->addRow(['mity_id' => 2, 'mser_id' => 2]);
        FakeDB::table('pivot')->addRow(['mity_id' => 2, 'mser_id' => 3]);

        Mity::addFakeRow(['id' => 1, 'mser_id' => 1, 'comment' => 'sss']);
        Mity::addFakeRow(['id' => 2, 'mser_id' => 1, 'comment' => 'aaa']);
        Mity::addFakeRow(['id' => 3, 'mser_id' => 2, 'comment' => 'bbb']);
        Mity::addFakeRow(['id' => 4, 'mser_id' => 2, 'comment' => 'ccc']);
        Mity::addFakeRow(['id' => 5, 'mser_id' => 3, 'comment' => 'ddd']);

        $user = Mser::where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->city);
        dd(get_class($user->city));
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[0]->id);
        $this->assertEquals(1, $user->comments[0]->user_id);
        $this->assertEquals('sss', $user->comments[0]->comment);

        $user = Mser::with('comments:user_id')->where('id', 1)->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(null, $user->comments[1]->id);
        $this->assertEquals(1, $user->comments[1]->user_id);
        $this->assertEquals(null, $user->comments[1]->comment);

        $user = Mser::with('comments')->where('id', 1)->get()->first();

        $this->assertInstanceOf(Collection::class, $user->comments);
        $this->assertEquals(2, $user->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $user->comments[1]);
        $this->assertEquals(2, $user->comments[1]->id);

        $comments = Mser::find(1)->load('comments');
        $this->assertInstanceOf(Collection::class, $comments->comments);
        $this->assertEquals(2, $comments->comments->count());
        $this->assertInstanceOf(ManyTosManyComment::class, $comments->comments[0]);
        $this->assertInstanceOf(ManyTosManyComment::class, $comments->comments[1]);
        $this->assertEquals(2, $comments->comments[1]->id);

        $this->assertEquals(2, Mser::find(1)->comments()->count());
        $this->assertEquals(1, Mser::find(1)->comments()->where('comment', 'aaa')->count());
        $this->assertEquals(1, Mser::find(1)->comments()->where('comment', 'aaa')->get()->count());
        $this->assertEquals(2, Mser::find(2)->comments()->count());
        $this->assertEquals(1, Mser::find(3)->comments()->count());
        $this->assertEquals(0, Mser::find(4)->comments()->count());

        $this->assertEquals(2, Mser::find(1)->comments->count());
        $this->assertEquals(2, Mser::find(2)->comments->count());
        $this->assertEquals(1, Mser::find(3)->comments->count());
        $this->assertEquals(0, Mser::find(4)->comments->count());

        $comments = Mser::find(3)->comments;
        $this->assertEquals('ddd', $comments[0]->comment);

        $comments = Mser::find(1)->comments;
        $this->assertEquals('sss', $comments[0]->comment);

        $this->assertEquals('aaa', Mser::find(1)->comments()->where('comment', 'aaa')->first()->comment);

        $this->assertEquals(1, Mity::query()->find(1)->user->id);
        $this->assertEquals(1, Mity::query()->find(1)->user()->count());

        $this->assertEquals(1, Mity::query()->find(2)->user->id);
        $this->assertEquals(1, Mity::query()->find(2)->user()->count());

        $this->assertEquals(2, Mity::query()->find(3)->user->id);
        $this->assertEquals(1, Mity::query()->find(3)->user()->count());

        $newUser = Mity::query()->find(3)->user()->create([
            'name' => 'created',
        ]);

        $this->assertNotNull($newUser->created_at);
        $this->assertNotNull($newUser->updated_at);
        $this->assertNotNull(Mser::find(5));
        $this->assertEquals(5, $newUser->id);
        $this->assertEquals('created', $newUser->name);

        $this->assertEquals('created', $newUser->name);
        $this->assertSame(Mser::getCreatedModel(), $newUser);
        $this->assertEquals(5, Mser::count());

        $comment = Mser::find(4)->comments()->create([
            'comment' => 'created!',
        ]);
        $this->assertEquals('created!', $comment->comment);
        $this->assertEquals(6, $comment->id);
        $this->assertEquals(4, $comment->user_id);
        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
        $this->assertSame(Mity::getCreatedModel(), $comment);
        $this->assertNull(Mity::getCreatedModel(1));
    }
}
