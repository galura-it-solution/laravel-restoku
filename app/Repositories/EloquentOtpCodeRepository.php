<?php

namespace App\Repositories;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;

class EloquentOtpCodeRepository implements OtpCodeRepository
{
    public function latestUnused(int $userId, string $type): ?OtpCode
    {
        return OtpCode::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('used_at')
            ->latest('id')
            ->first();
    }

    public function create(array $data): OtpCode
    {
        return OtpCode::create($data);
    }

    public function incrementAttempts(OtpCode $otp): OtpCode
    {
        $otp->increment('attempts');

        return $otp;
    }

    public function markUsed(OtpCode $otp): OtpCode
    {
        $otp->update(['used_at' => Carbon::now()]);

        return $otp;
    }
}
