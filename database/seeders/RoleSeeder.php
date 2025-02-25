<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'student',
                'display_name' => '学生',
                'description' => '普通学生用户'
            ],
            [
                'name' => 'teacher',
                'display_name' => '教师',
                'description' => '教师用户'
            ],
            [
                'name' => 'arbitration',
                'display_name' => '仲裁委员会',
                'description' => '仲裁委员会成员'
            ],
            [
                'name' => 'academic',
                'display_name' => '教务',
                'description' => '教务人员'
            ],
            [
                'name' => 'admin',
                'display_name' => '管理员',
                'description' => '系统管理员'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
} 