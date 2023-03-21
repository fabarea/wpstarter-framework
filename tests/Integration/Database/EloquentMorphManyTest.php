<?php

namespace WpStarter\Tests\Integration\Database\EloquentMorphManyTest;

use WpStarter\Database\Eloquent\Model;
use WpStarter\Database\Schema\Blueprint;
use WpStarter\Support\Carbon;
use WpStarter\Support\Facades\Schema;
use WpStarter\Support\Str;
use WpStarter\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphManyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
    }

    public function testUpdateModelWithDefaultWithCount()
    {
        $post = Post::create(['title' => Str::random()]);

        $post->update(['title' => 'new name']);

        $this->assertSame('new name', $post->title);
    }

    public function test_self_referencing_existence_query()
    {
        $post = Post::create(['title' => 'foo']);

        $comment = tap((new Comment(['name' => 'foo']))->commentable()->associate($post))->save();

        (new Comment(['name' => 'bar']))->commentable()->associate($comment)->save();

        $comments = Comment::has('replies')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Comment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function replies()
    {
        return $this->morphMany(self::class, 'commentable');
    }
}
