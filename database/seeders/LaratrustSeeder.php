<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncateLaratrustTables();

        // 1. Setup Admin Portal Roles & Permissions
        $adminRoles = [
            'admin' => [
                'companies' => 'create,read,update,delete,approve,suspend',
                'dids' => 'create,read,update,delete,assign',
                'invoices' => 'read,update,finalize,mark-paid',
                'calls' => 'read',
                'reports' => 'read',
                'admins' => 'create,read,update,delete',
                'logs' => 'read',
                'settings' => 'read,update',
            ],
            'manager' => [
                'companies' => 'create,read,update',
                'dids' => 'create,read,update,assign',
                'invoices' => 'read',
                'calls' => 'read',
                'reports' => 'read',
            ],
            'user' => [ // Viewer
                'companies' => 'read',
                'dids' => 'read',
                'invoices' => 'read',
                'calls' => 'read',
                'reports' => 'read',
            ],
        ];

        // 2. Setup Company Portal Roles & Permissions
        $companyRoles = [
            'admin' => [
                'company-profile' => 'read,update',
                'company-users' => 'create,read,update,delete',
                'dids' => 'read', // Read-only as requested
                'invoices' => 'read',
                'calls' => 'read,feedback',
                'logs' => 'read',
            ],
            'manager' => [
                'company-profile' => 'read',
                'dids' => 'read', // Read-only
                'invoices' => 'read',
                'calls' => 'read,feedback',
            ],
            'user' => [
                'dids' => 'read', // Read-only
                'calls' => 'read',
                'invoices' => 'read',
            ],
        ];

        // Create Permissions and Roles
        $this->createRolesAndPermissions($adminRoles, 'admin');
        $this->createRolesAndPermissions($companyRoles, 'company');

        // Create Default Users if enabled
        if (Config::get('laratrust_seeder.create_users')) {
            $this->createDefaultUsers($adminRoles, 'admin');
            $this->createDefaultUsers($companyRoles, 'company');
        }
    }

    protected function createRolesAndPermissions($roles, $scope)
    {
        foreach ($roles as $roleName => $modules) {
            // Create Role with scope suffix to verify uniqueness if needed, 
            // but for simplicity we'll use same names and handle scope via logic or different tables if necessary.
            // However, Laratrust roles are global by default. 
            // To separate, we will append scope to internal name but keep display name clean?
            // Actually, requirements said 'admin, manager, user' for both.
            // Laratrust doesn't natively scope roles to guards.
            // We will use prefix for internal name: 'admin-admin', 'company-admin'

            // Adjusting role name strategy:
            $internalRoleName = $scope . '-' . $roleName;

            $role = \App\Models\Role::firstOrCreate([
                'name' => $internalRoleName,
                'display_name' => ucfirst($scope) . ' ' . ucfirst($roleName),
                'description' => ucfirst($scope) . ' ' . ucfirst($roleName) . ' Role',
            ]);

            $permissions = [];

            foreach ($modules as $module => $perms) {
                foreach (explode(',', $perms) as $perm) {
                    $permissionName = $scope . '-' . $module . '-' . $perm;

                    $permissions[] = \App\Models\Permission::firstOrCreate([
                        'name' => $permissionName,
                        'display_name' => ucfirst($scope) . ' ' . ucfirst($perm) . ' ' . ucfirst($module),
                        'description' => ucfirst($scope) . ' ' . ucfirst($perm) . ' ' . ucfirst($module),
                    ])->id;
                }
            }

            $role->permissions()->sync($permissions);
        }
    }

    protected function createDefaultUsers($roles, $scope)
    {
        foreach ($roles as $roleName => $modules) {
            $internalRoleName = $scope . '-' . $roleName;
            $role = \App\Models\Role::where('name', $internalRoleName)->first();

            if ($scope === 'admin') {
                $user = \App\Models\Admin::create([
                    'name' => ucfirst($roleName),
                    'email' => $roleName . '@admin.com',
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]);
            } else {
                // For company, we need a company first
                $company = \App\Models\Company::firstOrCreate(
                    ['email' => 'demo@company.com'],
                    [
                        'phone' => '1234567890',
                        'business_name' => 'Demo Company',
                        'verify_email' => true,
                        'verify_phone' => true,
                        'status' => 'active',
                    ]
                );

                $user = \App\Models\CompanyUser::create([
                    'company_id' => $company->id,
                    'name' => ucfirst($roleName),
                    'email' => $roleName . '@company.com',
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]);
            }

            $user->addRole($role);
        }
    }

    /**
     * Truncates all the laratrust tables and the users table
     *
     * @return  void
     */
    public function truncateLaratrustTables()
    {
        $this->command->info('Truncating User, Role and Permission tables');
        Schema::disableForeignKeyConstraints();

        DB::table('permission_role')->truncate();
        DB::table('permission_user')->truncate();
        DB::table('role_user')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();

        if (Config::get('laratrust_seeder.create_users')) {
            DB::table('admins')->truncate();
            DB::table('company_users')->truncate();
            DB::table('companies')->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}
