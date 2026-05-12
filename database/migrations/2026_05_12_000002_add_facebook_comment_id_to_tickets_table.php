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
            if (!Schema::hasColumn('tickets', 'facebook_comment_id')) {
                $table->string('facebook_comment_id')->nullable()->after('facebook_post_id')->index();
            }
            if (!Schema::hasColumn('tickets', 'summary')) {
                $table->text('summary')->nullable()->after('initial_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'facebook_comment_id')) {
                $table->dropColumn('facebook_comment_id');
            }
        });
    }
};
