<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_creates_roles_and_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertNotNull(Role::where('name', 'admin')->first());
        $this->assertNotNull(Role::where('name', 'staff')->first());
        $this->assertNotNull(Role::where('name', 'customer')->first());

        $this->assertNotEmpty(Permission::all());

        $admin = Role::where('name', 'admin')->first();
        $staff = Role::where('name', 'staff')->first();
        $customer = Role::where('name', 'customer')->first();

        $this->assertTrue($admin->hasPermissionTo('products.create'));
        $this->assertTrue($staff->hasPermissionTo('products.view'));
        $this->assertFalse($staff->hasPermissionTo('products.delete'));
        $this->assertTrue($customer->hasPermissionTo('orders.view'));
        $this->assertFalse($customer->hasPermissionTo('products.delete'));

        $adminUser = User::where('email', 'admin@confambuy.com')->first();
        $staffUser = User::where('email', 'tojurex@gmail.com')->first();

        $this->assertTrue($adminUser->hasRole('admin'));
        $this->assertTrue($staffUser->hasRole('staff'));
    }
}
