<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['parent', 'role'])->paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    public function getRoles()
    {
        $roles = Role::all();
        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|unique:users',
            'parent_id' => 'nullable|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'role_id' => $validated['role_id']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => '用户创建成功',
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'nullable|string|unique:users,phone,' . $id,
            'parent_id' => 'nullable|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::findOrFail($id);
        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => '用户更新成功',
            'data' => $user
        ]);
    }
} 