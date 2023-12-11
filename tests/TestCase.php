<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
// use App\Contracts\UserContract;


abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function actingAs(UserContract $user , $abilities = ['*'])
    {
        Sanctum::actingAs($user,$abilities);
        return $this;
    }
}
