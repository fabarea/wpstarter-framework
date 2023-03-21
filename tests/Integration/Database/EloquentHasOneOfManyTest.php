<?php

namespace WpStarter\Tests\Integration\Database\EloquentHasOneOfManyTest;

use WpStarter\Database\Eloquent\Model;
use WpStarter\Support\Facades\Schema;
use WpStarter\Tests\Integration\Database\DatabaseTestCase;

class EloquentHasOneOfManyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function ($table) {
            $table->id();
        });

        Schema::create('logins', function ($table) {
            $table->id();
            $table->foreignId('user_id');
        });
    }

    public function testItOnlyEagerLoadsRequiredModels()
    {
        $this->retrievedLogins = 0;
        User::getEventDispatcher()->listen('eloquent.retrieved:*', function ($event, $models) {
            foreach ($models as $model) {
                if (get_class($model) == Login::class) {
                    $this->retrievedLogins++;
                }
            }
        });

        $user = User::create();
        $user->latest_login()->create();
        $user->latest_login()->create();
        $user = User::create();
        $user->latest_login()->create();
        $user->latest_login()->create();

        User::with('latest_login')->get();

        $this->assertSame(2, $this->retrievedLogins);
    }
}

class User extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function latest_login()
    {
        return $this->hasOne(Login::class)->ofMany();
    }
}

class Login extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
