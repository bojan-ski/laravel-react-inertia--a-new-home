<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@newbeginning.test')->exists()) {
            return;
        }

        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@newbeginning.test',
            'password' => 'password',
        ]);
    }
}
