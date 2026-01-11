<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LaratrustSeeder::class,
            AdminSeeder::class,
            MassiveDataSeeder::class, // 10 Companies with massive data
        ]);
    }
}
