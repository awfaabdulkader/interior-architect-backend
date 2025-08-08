<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default admin user (Khaoula) for deployment
        User::create([
            'name' => 'Khaoula Elakel',
            'email' => 'khaoulaelakel201@gmail.com',
            'password' => Hash::make('USERNAME_pa$$w0rd'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Default admin user created successfully!');
        $this->command->info('📧 Email: khaoula@interior-portfolio.com');
        $this->command->info('🔑 Password: Khaoula@2025!');
        $this->command->warn('⚠️  Please change this password after first login!');
    }
}
