<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AdminPasswordResetMail;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.submit'), [
            'email'    => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        User::factory()->create([
            'email'    => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.submit'), [
            'email'    => 'testuser@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_register_successfully(): void
    {
        $response = $this->post(route('register.submit'), [
            'name'                  => 'New User',
            'email'                 => 'newuser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name'  => 'New User',
        ]);
    }

    public function test_admin_forgot_password_queues_reset_email_without_user_enumeration(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'adminreset@example.com',
        ]);

        $response = $this->post(route('admin.password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Mail::assertQueued(AdminPasswordResetMail::class, static function (AdminPasswordResetMail $mail) use ($user): bool {
            return $mail->hasTo($user->email) && str_contains($mail->resetUrl, 'reset-password/');
        });

        // Unknown email returns identical status without sending mail (anti-enumeration)
        $unknownResponse = $this->post(route('admin.password.email'), [
            'email' => 'nobody@example.com',
        ]);
        $unknownResponse->assertSessionHas('status');
        Mail::assertQueuedCount(1);
    }
}
