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
        User::updateOrCreate(
            ['email' => 'admin@rochasports.com.br'],
            [
                'name' => 'Rocha Sports Admin',
                'password' => Hash::make('password'),
            ],
        );

        $this->call(CommerceSeeder::class);
    }
}
