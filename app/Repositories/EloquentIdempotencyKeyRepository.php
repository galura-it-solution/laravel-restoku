<?php

namespace App\Repositories;

use App\Models\IdempotencyKey;

class EloquentIdempotencyKeyRepository implements IdempotencyKeyRepository
{
    public function findForUpdate(int $userId, string $key): ?IdempotencyKey
    {
        return IdempotencyKey::where('user_id', $userId)
            ->where('key', $key)
            ->lockForUpdate()
            ->first();
    }

    public function create(array $data): IdempotencyKey
    {
        return IdempotencyKey::create($data);
    }

    public function update(IdempotencyKey $key, array $data): IdempotencyKey
    {
        $key->update($data);

        return $key;
    }
}
