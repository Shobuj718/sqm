<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facebook_page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->string('title');
            $table->string('source_type')->default('manual');
            $table->string('source_reference')->nullable();
            $table->longText('content');
            $table->string('content_hash', 64)->index();
            $table->string('status')->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['facebook_page_id', 'status']);
            $table->index(['source_type', 'source_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_documents');
    }
};
