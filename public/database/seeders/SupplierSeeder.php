<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            'Secure Premises Ltd',
            'Eaglewatchsystems Ltd',
            'Anchorelite Ltd',
            '⁠AMK Global Ltd',
            'Alitesecurities Ltd',
        ];

        foreach ($suppliers as $name) {
            DB::table('suppliers')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
