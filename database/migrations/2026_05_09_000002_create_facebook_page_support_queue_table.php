<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_page_support_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('facebook_page_id');
            $table->unsignedBigInteger('support_queue_id');
            $table->primary(['facebook_page_id', 'support_queue_id']);

            $table->foreign('facebook_page_id')
                ->references('id')
                ->on('facebook_pages')
                ->onDelete('cascade');

            $table->foreign('support_queue_id')
                ->references('id')
                ->on('support_queues')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_page_support_queue');
    }
};
