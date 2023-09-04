<?php

namespace Imanghafoori\EloquentMockery\Tests\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Imanghafoori\EloquentMockery\FakeDB;
use PHPUnit\Framework\TestCase;

class PolymorphTest extends TestCase
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
    public function polymorph_one_to_one_test()
    {
        FakeDB::addRow('videos', ['id' => 3, 'name' => 'video_1']);
        PolymorphVideo::first()->comment()->create(['body' => 'nice video!']);

        $video = PolymorphComment::query()->first()->commentable;

        $this->assertInstanceOf(PolymorphVideo::class, $video);
        $this->assertEquals(3, $video->id);
        $this->assertEquals('video_1', $video->name);

        $comment = $video->comment;
        $this->assertInstanceOf(PolymorphComment::class, $comment);
        $this->assertEquals(1, $comment->id);
        $this->assertEquals('nice video!', $comment->body);
        $this->assertEquals(3, $comment->commentable_id);
        $this->assertEquals(PolymorphVideo::class, $comment->commentable_type);

        PolymorphVideo::first()->comment()->delete();
        $this->assertEquals(PolymorphVideo::first()->comment()->count(), 0);

        Relation::morphMap(['video' => PolymorphVideo::class]);

        PolymorphVideo::first()->comment()->create(['body' => 'good video!']);

        $comment = PolymorphVideo::first()->comment()->first();

        $this->assertEquals('good video!', $comment->body);
        $this->assertEquals(3, $comment->commentable_id);
        $this->assertEquals('video', $comment->commentable_type);

        $this->assertEquals(PolymorphVideo::first()->comment()->count(), 1);

        Relation::$morphMap = [];
    }

    /**
     * @test
     */
    public function polymorph_one_to_many()
    {
        FakeDB::addRow('videos', ['id' => 3, 'name' => 'video_1']);
        PolymorphVideo::first()->comments()->create(['body' => 'nice video1']);
        PolymorphVideo::first()->comments()->create(['body' => 'nice video2']);
        PolymorphVideo::first()->comments()->create(['body' => 'nice video3']);

        $video = PolymorphComment::query()->first()->commentable;

        $this->assertInstanceOf(PolymorphVideo::class, $video);
        $this->assertEquals(3, $video->id);
        $this->assertEquals('video_1', $video->name);

        $comments = $video->comments;
        $this->assertInstanceOf(Collection::class, $comments);
        $this->assertCount(3, $comments);

        $comments = $video->comments()->get();
        $this->assertInstanceOf(Collection::class, $comments);
        $this->assertCount(3, $comments);

        $comment = $comments[0];
        $this->assertEquals(1, $comment->id);
        $this->assertEquals('nice video1', $comment->body);
        $this->assertEquals(3, $comment->commentable_id);
        $this->assertEquals(PolymorphVideo::class, $comment->commentable_type);

        $comment = $comments[2];
        $this->assertEquals(3, $comment->id);
        $this->assertEquals('nice video3', $comment->body);
        $this->assertEquals(3, $comment->commentable_id);
        $this->assertEquals(PolymorphVideo::class, $comment->commentable_type);

        $this->assertEquals(PolymorphVideo::first()->comments()->count(), 3);

        PolymorphVideo::first()->comments()->delete();

        $this->assertEquals(PolymorphVideo::first()->comments()->count(), 0);
    }

    /**
     * @test
     */
    public function polymorph_many_to_many()
    {
        $video = PolymorphVideo::create(['name' => 'vid 1']);
        $audio = PolymorphAudio::create(['name' => 'aud 1']);
        $tag = PolymorphTag::create(['name' => 'aud 1']);

        $tag->videos()->attach($video);

        $this->assertCount(1, $tag->videos);
        $this->assertEquals('vid 1', $tag->videos[0]->name);

        $rows = FakeDB::table('taggables')->allRows();

        $this->assertEquals([
            'polymorph_tag_id' => 1,
            'taggable_id' => 1,
            'taggable_type' => PolymorphVideo::class,
            'id' => 1,
        ], $rows[0]);
        $count = FakeDB::table('taggables')->count();
        $this->assertEquals(1, $count);

        $tag->audios()->attach($audio);

        $this->assertCount(1, $tag->audios);
        $this->assertEquals('aud 1', $tag->audios[0]->name);

        $rows = FakeDB::table('taggables')->allRows();

        $count = FakeDB::table('taggables')->count();
        $this->assertEquals(2, $count);

        $this->assertEquals([
            'polymorph_tag_id' => 1,
            'taggable_id' => 1,
            'taggable_type' => PolymorphVideo::class,
            'id' => 1,
        ], $rows[0]);

        $this->assertEquals([
            'polymorph_tag_id' => 1,
            'taggable_id' => 1,
            'taggable_type' => PolymorphAudio::class,
            'id' => 2,
        ], $rows[1]);
    }
}

class PolymorphComment extends Model
{
    public $fillable = ['body', 'commentable_id', 'commentable_type'];

    protected $table = 'comments';

    public function commentable(): MorphTo
    {
        return $this->morphTo('commentable', 'commentable_type', 'commentable_id');
    }
}

class PolymorphVideo extends Model
{
    public $fillable = ['name'];

    protected $table = 'videos';

    public function comment(): MorphOne
    {
        return $this->morphOne(PolymorphComment::class, 'commentable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(PolymorphComment::class, 'commentable');
    }
}

class PolymorphAudio extends Model
{
    public $fillable = ['name'];

    protected $table = 'audios';

    public function comments(): MorphMany
    {
        return $this->morphMany(PolymorphComment::class, 'commentable');
    }
}

class PolymorphTag extends Model
{
    public $fillable = ['name'];

    protected $table = 'tags';

    public function audios(): MorphToMany
    {
        return $this->morphedByMany(PolymorphAudio::class, 'taggable');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(PolymorphVideo::class, 'taggable');
    }
}

