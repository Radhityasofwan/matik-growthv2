<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Ubah definisi ENUM status agar mencakup 'nonactive'
        DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('trial','active','nonactive','converted','churn') NOT NULL DEFAULT 'trial'");
    }

    public function down(): void
    {
        // Kembalikan ke enum sebelumnya
        DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('trial','active','converted','churn') NOT NULL DEFAULT 'trial'");
    }
};
