<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            User::with('employee.role')
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'role'
                )
                ->get()
                ->map(function (User $user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'account_type' => $user->role,
                        'employee' => $user->employee,
                    ];
                })
        );
    }
public function show(User $user): JsonResponse
{
    $user->load('employee.role');

    return response()->json([
        'id' => $user->id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'account_type' => $user->role,
        'employee' => $user->employee,
    ]);
}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'account_type' => ['required', 'in:admin,user'],
            'role_id' => [
                'nullable',
                'exists:roles,id',
                'required_if:account_type,user',
            ],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['account_type'],
        ]);

        if ($data['account_type'] === 'user') {
            Employee::create([
                'user_id' => $user->id,
                'role_id' => $data['role_id'],
            ]);
        }

        return response()->json(
            $user->load('employee.role'),
            201
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $user->id,
            ],
            'account_type' => ['required', 'in:admin,user'],
            'role_id' => [
                'nullable',
                'exists:roles,id',
                'required_if:account_type,user',
            ],
        ]);

        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role' => $data['account_type'],
        ]);

        if ($data['account_type'] === 'user') {
            $user->employee()->updateOrCreate(
                ['user_id' => $user->id],
                ['role_id' => $data['role_id']],
            );
        } else {
            $user->employee?->delete();
        }

        return response()->json(
            $user->load('employee.role')
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}