<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'channel')) {
                $table->enum('channel', ['messenger', 'comment'])->default('messenger')->after('initial_message');
            }

            if (!Schema::hasColumn('tickets', 'facebook_post_id')) {
                $table->string('facebook_post_id')->nullable()->after('channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'facebook_post_id')) {
                $table->dropColumn('facebook_post_id');
            }

            if (Schema::hasColumn('tickets', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
