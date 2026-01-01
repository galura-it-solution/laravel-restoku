<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $query = User::query()->select(['id', 'name', 'email', 'role']);

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->orderBy('name')->paginate($perPage);

        return UserResource::collection($users);
    }
}
