<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @group auth
 */
class DeactivatedUsersCanNotLogInTest extends TestCase
{
    /**
     * @test
     */
    public function activated_user_can_log_in(): void
    {
        $user = factory(User::class)->create(['password' => Hash::make('tester'), 'deactivated_at' => null]);

        $this->call('POST', '/login', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function deactivated_user_cannot_log_in(): void
    {
        $user = factory(User::class)->create(['password' => Hash::make('tester'), 'deactivated_at' => '2020-01-01']);

        $this->call('POST', '/login', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/');

        $this->call('GET', '/', ['email' => $user->email, 'password' => 'tester'])
            ->assertRedirect('/login');
    }
}
