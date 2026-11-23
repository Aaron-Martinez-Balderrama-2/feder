<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        $user = User::where('role', 'admin')->first();
        $this->assertNotNull($user);

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::where('role', 'admin')->first();
        $this->assertNotNull($user);

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token-123456',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::where('role', 'admin')->first();
        $this->assertNotNull($user);

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'supersecurepassword99',
            'password_confirmation' => 'supersecurepassword99',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('success');

        // Verify user can log in with new password
        $user->refresh();
        $this->assertTrue(Hash::check('supersecurepassword99', $user->password));

        // Restore password back to 'password' for convenience
        $user->password = 'password';
        $user->save();
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
