<?php

namespace WpStarter\Tests\Integration\Foundation\Testing\Concerns;

use WpStarter\Database\Schema\Blueprint;
use WpStarter\Foundation\Auth\User as Authenticatable;
use WpStarter\Http\Request;
use WpStarter\Support\Facades\Auth;
use WpStarter\Support\Facades\Route;
use WpStarter\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class InteractsWithAuthenticationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', AuthenticationTestUser::class);

        $app['config']->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('remember_token')->default(null)->nullable();
            $table->tinyInteger('is_active')->default(0);
        });

        AuthenticationTestUser::create([
            'username' => 'taylorotwell',
            'email' => 'taylorotwell@laravel.com',
            'password' => ws_bcrypt('password'),
            'is_active' => true,
        ]);
    }

    public function testActingAsIsProperlyHandledForSessionAuth()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth']);

        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();

        $this->actingAs($user)
            ->get('/me')
            ->assertSuccessful()
            ->assertSeeText('Hello taylorotwell');
    }

    public function testActingAsIsProperlyHandledForAuthViaRequest()
    {
        Route::get('me', function (Request $request) {
            return 'Hello '.$request->user()->username;
        })->middleware(['auth:api']);

        Auth::viaRequest('api', function ($request) {
            return $request->user();
        });

        $user = AuthenticationTestUser::where('username', '=', 'taylorotwell')->first();

        $this->actingAs($user, 'api')
            ->get('/me')
            ->assertSuccessful()
            ->assertSeeText('Hello taylorotwell');
    }
}

class AuthenticationTestUser extends Authenticatable
{
    public $table = 'users';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
