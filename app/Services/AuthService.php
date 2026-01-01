<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\OtpCodeRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private OtpCodeRepository $otpCodes
    ) {
    }

    public function register(array $data): User
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'customer',
            'two_factor_enabled' => true,
        ]);

        $this->sendOtp($user, 'login');

        return $user;
    }

    public function login(array $data): User
    {
        $user = $this->users->findByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $this->sendOtp($user, 'login');

        return $user;
    }

    public function verifyOtp(array $data): string
    {
        $user = $this->users->findByEmail($data['email']);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User tidak ditemukan.'],
            ]);
        }

        $otp = $this->otpCodes->latestUnused($user->id, 'login');

        if (!$otp) {
            throw ValidationException::withMessages([
                'code' => ['OTP tidak ditemukan atau sudah digunakan.'],
            ]);
        }

        if ($otp->attempts >= $otp->max_attempts) {
            throw ValidationException::withMessages([
                'code' => ['OTP melebihi batas percobaan.'],
            ]);
        }

        if (Carbon::now()->greaterThan($otp->expires_at)) {
            throw ValidationException::withMessages([
                'code' => ['OTP sudah expired.'],
            ]);
        }

        $this->otpCodes->incrementAttempts($otp);

        if (!Hash::check($data['code'], $otp->code)) {
            throw ValidationException::withMessages([
                'code' => ['OTP tidak valid.'],
            ]);
        }

        $this->otpCodes->markUsed($otp);

        return $user->createToken('api-token')->plainTextToken;
    }

    private function sendOtp(User $user, string $type): void
    {
        $code = (string) random_int(100000, 999999);

        $this->otpCodes->create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(5),
            'attempts' => 0,
            'max_attempts' => 5,
        ]);

        Mail::raw("Kode OTP Anda: {$code}", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('OTP Login');
        });
    }
}
