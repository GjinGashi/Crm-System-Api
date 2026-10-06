<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['user', 'role']);

        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'role_id' => $data['role_id'],
        ]);

        return response()->json(
            $employee->load(['user', 'role']),
            201
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json(
            $employee->load(['user.tasks.project', 'role'])
        );
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $employee->user_id,
            ],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $employee->user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        $employee->update([
            'role_id' => $data['role_id'],
        ]);

        return response()->json(
            $employee->load(['user', 'role'])
        );
    }
    public function archive(Employee $employee): JsonResponse
    {
        $employee->update([
            'archived_at' => now(),
        ]);

        return response()->json([
            'message' => 'Employee archived successfully.',
        ]);
    }

    public function restore(Employee $employee): JsonResponse
    {
        $employee->update([
            'archived_at' => null,
        ]);

        return response()->json([
            'message' => 'Employee restored successfully.',
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        if ($employee->archived_at === null) {
            return response()->json([
                'message' => 'Only archived employees can be deleted.',
            ], 403);
        }

        $employee->user->delete();

        return response()->json([
            'message' => 'Employee deleted successfully.',
        ]);
    }
}
