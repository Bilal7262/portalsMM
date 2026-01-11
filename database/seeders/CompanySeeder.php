<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tech Corp (Static)
        $c1 = Company::create([
            'business_name' => 'Tech Corp',
            'email' => 'contact@tech.com',
            'phone' => '+15550000001',
            'status' => 'active',
            'verify_email' => true,
            'verify_phone' => true,
        ]);

        $password = Hash::make('password');
        
        $u1 = CompanyUser::create([
            'company_id' => $c1->id,
            'name' => 'John Doe',
            'email' => 'john@tech.com',
            'password' => $password,
            'status' => 'active'
        ]);
        
        $adminRole = Role::where('name', 'company-admin')->first();
        if ($adminRole) $u1->addRole($adminRole);


        // 2. Bulk Insert 100K Companies
        $total = 100000;
        $chunkSize = 1000;
        
        $this->command->info("Seeding $total companies...");
        $this->command->getOutput()->progressStart($total);

        // Get starting ID for bulk insert strategy
        // NOTE: This assumes sequential IDs and no race conditions (safe for seeding)
        $nextId = Company::max('id') + 1;

        for ($i = 0; $i < $total / $chunkSize; $i++) {
            $companies = [];
            $users = [];
            $now = now();

            for ($j = 0; $j < $chunkSize; $j++) {
                $currentId = $nextId + $j;
                $companies[] = [
                    'id' => $currentId,
                    'business_name' => "Company $currentId",
                    'email' => "company{$currentId}@example.com",
                    'phone' => "+1555" . str_pad($currentId, 7, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'verify_email' => true,
                    'verify_phone' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $users[] = [
                    'company_id' => $currentId,
                    'name' => "User $currentId",
                    'email' => "user{$currentId}@example.com",
                    'password' => $password, // reuse hash for performance
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Company::insert($companies);
            CompanyUser::insert($users);

            $nextId += $chunkSize;
            $this->command->getOutput()->progressAdvance($chunkSize);
        }

        $this->command->getOutput()->progressFinish();
    }
}
