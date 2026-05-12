<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE tickets SET status = 'waiting' WHERE status = 'in_progress'");
        DB::statement("UPDATE tickets SET status = 'solved' WHERE status = 'resolved'");
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','waiting','solved','closed') NOT NULL DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE tickets SET status = 'in_progress' WHERE status = 'waiting'");
        DB::statement("UPDATE tickets SET status = 'resolved' WHERE status = 'solved'");
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open'");
    }
};
