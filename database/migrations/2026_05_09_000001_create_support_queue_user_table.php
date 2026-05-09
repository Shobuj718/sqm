<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_queue_user', function (Blueprint $table) {
            $table->unsignedBigInteger('support_queue_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['support_queue_id', 'user_id']);

            $table->foreign('support_queue_id')
                ->references('id')
                ->on('support_queues')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_queue_user');
    }
};
