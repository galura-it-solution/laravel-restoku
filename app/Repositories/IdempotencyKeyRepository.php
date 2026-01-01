<?php

namespace App\Repositories;

use App\Models\IdempotencyKey;

interface IdempotencyKeyRepository
{
    public function findForUpdate(int $userId, string $key): ?IdempotencyKey;
    public function create(array $data): IdempotencyKey;
    public function update(IdempotencyKey $key, array $data): IdempotencyKey;
}
