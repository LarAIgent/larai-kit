<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_ingestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')
                ->constrained('ai_assets')
                ->cascadeOnDelete();
            $table->string('state')->default('queued'); // queued, parsing, chunking, embedding, indexed, failed
            $table->text('error')->nullable();
            $table->unsignedInteger('chunk_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_ingestions');
    }
};
