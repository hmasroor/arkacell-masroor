<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Factories\UserFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Tenant::factory(5)->create()->each(function (Tenant $tenant) {
            Product::factory(10)->create([
                'tenant_id' => $tenant->id,
            ]);

            Warehouse::factory(3)->create([
                'tenant_id' => $tenant->id,
            ]);
        });

        User::create([
            'name' => 'حسین مسرور',
            'email' => 'hmasroor@arkacell.com',
            'password' => Hash::Make('Test@1234'),
        ]);
    }
}
