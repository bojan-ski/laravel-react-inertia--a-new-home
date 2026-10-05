<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $guard ??= $this->app['config']->get('auth.defaults.guard');

        if ($this->app->bound('session')) {
            $this->app['session']->forget('password_hash_'.$guard);
        }

        return parent::actingAs($user, $guard);
    }
}
