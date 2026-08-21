<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group auth
 */
#[\PHPUnit\Framework\Attributes\Group('auth')]

class DeactivatedUsersCanNotLogInTest extends TestCase
{
    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function activated_user_can_log_in()
    {
        $user = factory(User::class)->create(['password' => Hash::make('tester'), 'deactivated_at' => null]);

        $this->call('POST', '/login', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/home');
    }
 
    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function deactivated_user_cannot_log_in()
    {
        $user = factory(User::class)->create(['password' => Hash::make('tester'), 'deactivated_at' => '2020-01-01']);

        $this->call('POST', '/login', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/');

        $this->call('GET', '/', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/login');
    }
    
}
