<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_otp_issues_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'password' => 'password',
        ]);

        OtpCode::create([
            'user_id' => $user->id,
            'code' => Hash::make('123456'),
            'type' => 'login',
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
            'max_attempts' => 5,
        ]);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }
}
