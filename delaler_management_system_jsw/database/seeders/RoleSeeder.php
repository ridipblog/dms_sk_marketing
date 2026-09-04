<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_name' => 'Super Admin',
                'description' => 'System-wide administrator with full privileges.',
                'priority' => 1,
                'status' => 'active',
            ],
            [
                'role_name' => 'Admin Users',
                'description' => 'Administrative users with management capabilities.',
                'priority' => 2,
                'status' => 'active',
            ],
            [
                'role_name' => 'Branch Manager (BM)',
                'description' => 'Manages business operations at a branch level.',
                'priority' => 3,
                'status' => 'active',
            ],
            [
                'role_name' => 'Area Sales Manager (ASM)',
                'description' => 'Responsible for sales performance in specific areas.',
                'priority' => 4,
                'status' => 'active',
            ],
            [
                'role_name' => 'Assistant Section Officer (ASO)',
                'description' => 'Assists with regional dealer accounts and field operations.',
                'priority' => 5,
                'status' => 'active',
            ],
            [
                'role_name' => 'Dealer',
                'description' => 'Standard business dealer account.',
                'priority' => 6,
                'status' => 'active',
            ],
            [
                'role_name' => 'Finance Team',
                'description' => 'Handles transaction, invoice, and payment tracking.',
                'priority' => 7,
                'status' => 'active',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['role_name' => $roleData['role_name']],
                [
                    'description' => $roleData['description'],
                    'priority' => $roleData['priority'],
                    'status' => $roleData['status'],
                ]
            );
        }
    }
}
