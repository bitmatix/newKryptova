<?php

use Illuminate\Database\Seeder;
use App\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = Admin::create([
            'name' => 'Harsukh Makwana',
            'email' => 'harsukh21@gmail.com',
            'password' => bcrypt('Har#$785'),
            'email_changes' => 'harsukh21@gmail.com'
        ]);
        $role = Role::create(['name' => 'Admin', 'guard_name' => 'admin']);
        $permissions = Permission::pluck('id', 'id')->all();
        $role->syncPermissions($permissions);
        $user->assignRole([$role->id]);
    }
}
