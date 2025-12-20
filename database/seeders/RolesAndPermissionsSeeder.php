<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [
            // Course permissions
            'view courses',
            'view own courses',
            'create courses',
            'edit courses',
            'edit own courses',
            'delete courses',
            'delete own courses',
            'restore courses',
            'force delete courses',
            'publish courses',
            'archive courses',

            // Page permissions
            'view pages',
            'view course pages',
            'create pages',
            'edit pages',
            'edit course pages',
            'delete pages',
            'delete course pages',
            'restore pages',
            'publish pages',
            'reorder pages',

            // Discussion permissions
            'view discussions',
            'view course discussions',
            'create discussions',
            'edit discussions',
            'edit own discussions',
            'delete discussions',
            'delete own discussions',
            'close discussions',
            'open discussions',

            // Discussion Post permissions
            'view discussion posts',
            'create discussion posts',
            'edit discussion posts',
            'edit own discussion posts',
            'delete discussion posts',
            'delete own discussion posts',
            'publish discussion posts',

            // User permissions
            'view users',
            'create users',
            'edit users',
            'edit own profile',
            'delete users',
            'restore users',
            'force delete users',
            'assign roles',
            'manage permissions',

            // User Progress permissions
            'view user progress',
            'view own progress',
            'update user progress',
            'update own progress',
            'delete user progress',

            // Course enrollment permissions
            'enroll students',
            'unenroll students',
            'assign instructors',
            'remove instructors',
            'view course students',
            'view course instructors',

            // System permissions
            'view activity log',
            'manage system settings',
            'view reports',
            'export data',
        ];

        // Create all permissions
        DB::beginTransaction();

        try {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }

            // Create roles and assign permissions
            $this->createAdminRole();
            $this->createInstructorRole();
            $this->createStudentRole();

            DB::commit();

            $this->command->info('Roles and Permissions seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding roles and permissions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create Admin role with all permissions
     */
    private function createAdminRole(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // Admins get all permissions
        $admin->syncPermissions(Permission::all());

        $this->command->info('Admin role created with all permissions');
    }

    /**
     * Create Instructor role with course management permissions
     */
    private function createInstructorRole(): void
    {
        $instructor = Role::firstOrCreate(['name' => 'Instructor', 'guard_name' => 'web']);

        $instructorPermissions = [
            // Course permissions
            'view courses',
            'view own courses',
            'create courses',
            'edit own courses',
            'delete own courses',
            'publish courses',
            'archive courses',

            // Page permissions
            'view pages',
            'view course pages',
            'create pages',
            'edit course pages',
            'delete course pages',
            'publish pages',
            'reorder pages',

            // Discussion permissions
            'view discussions',
            'view course discussions',
            'create discussions',
            'edit discussions',
            'edit own discussions',
            'delete own discussions',
            'close discussions',
            'open discussions',

            // Discussion Post permissions
            'view discussion posts',
            'create discussion posts',
            'edit own discussion posts',
            'delete own discussion posts',
            'publish discussion posts',

            // User permissions
            'view users',
            'edit own profile',

            // User Progress permissions
            'view user progress',
            'view own progress',
            'update own progress',

            // Course enrollment permissions
            'enroll students',
            'unenroll students',
            'view course students',
            'view course instructors',

            // Limited system permissions
            'view reports',
            'export data',
        ];

        $instructor->syncPermissions($instructorPermissions);

        $this->command->info('Instructor role created with course management permissions');
    }

    /**
     * Create Student role with viewing and participation permissions
     */
    private function createStudentRole(): void
    {
        $student = Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $studentPermissions = [
            // Course permissions
            'view courses',
            'view own courses',

            // Page permissions
            'view pages',
            'view course pages',

            // Discussion permissions
            'view discussions',
            'view course discussions',
            'create discussions',
            'edit own discussions',
            'delete own discussions',

            // Discussion Post permissions
            'view discussion posts',
            'create discussion posts',
            'edit own discussion posts',
            'delete own discussion posts',

            // User permissions
            'edit own profile',

            // User Progress permissions
            'view own progress',
            'update own progress',
        ];

        $student->syncPermissions($studentPermissions);

        $this->command->info('Student role created with viewing and participation permissions');
    }
}
