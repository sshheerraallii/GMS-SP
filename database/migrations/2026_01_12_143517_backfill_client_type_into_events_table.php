<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map clients.type -> events.client_type
        // 'VAT' => 'VAT'
        // everything else => 'NON_VAT'
        DB::statement("
            UPDATE events e
            JOIN clients c ON c.id = e.client_id
            SET e.client_type = CASE
                WHEN UPPER(TRIM(c.type)) = 'VAT' THEN 'VAT'
                ELSE 'NON_VAT'
            END
        ");
    }

    public function down(): void
{
    DB::statement("UPDATE events SET client_type = 'NON_VAT'");
}

};
