<?php

namespace App\Repositories;

use App\Models\OtpCode;

interface OtpCodeRepository
{
    public function latestUnused(int $userId, string $type): ?OtpCode;
    public function create(array $data): OtpCode;
    public function incrementAttempts(OtpCode $otp): OtpCode;
    public function markUsed(OtpCode $otp): OtpCode;
}
