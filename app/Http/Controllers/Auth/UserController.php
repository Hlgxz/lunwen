<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class UserController extends Controller
{
    // 定义角色常量
    const ROLE_STUDENT = 'student';
    const ROLE_TEACHER = 'teacher';
    const ROLE_ARBITRATION = 'arbitration';
    const ROLE_ACADEMIC = 'academic';
    const ROLE_ADMIN = 'admin';

    /**
     * Get authenticated user.
     */
    public function current(Request $request)
    {
        $user = $request->user();
        // 如果用户没有角色，分配默认学生角色
        if (!$user->role_id) {
            $defaultRole = Role::where('name', 'student')->first();
            $user->role_id = $defaultRole->id;
            $user->save();
        }
        // 加载用户角色关系
        $user->load('role');
        return response()->json($user);
    }
}
