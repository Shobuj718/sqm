<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('support_queue_id')->nullable()->after('assigned_to');

            $table->foreign('support_queue_id')
                ->references('id')
                ->on('support_queues')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['support_queue_id']);
            $table->dropColumn('support_queue_id');
        });
    }
};
