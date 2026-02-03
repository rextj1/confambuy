<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Access Control & Users
            PermissionSeeder::class,
            AdminUserSeeder::class,
            UserSeeder::class, // Creates customers and addresses

            // 2. Catalog Data
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class, // Handles Products, SKUs, Inventory, and Images
            MarketingSeeder::class, // Coupons and Promotions

            // 3. Transactional Data
            OrderSeeder::class, // Orders, Items, Payments, Shipments
            ReviewSeeder::class,
        ]);
    }
}