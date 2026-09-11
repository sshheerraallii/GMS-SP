<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SecurityGuard;

class SecurityGuardSeeder extends Seeder
{
    public function run(): void
    {
        SecurityGuard::factory()
            ->count(200)
            ->create();
    }
}
