<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assets', function (Blueprint $table) {
            $table->id();
            $table->string('source_name');
            $table->string('source_type');
            $table->string('source_disk')->nullable();
            $table->string('source_path')->nullable();
            $table->string('source_url')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('tags')->nullable();
            $table->json('scope')->nullable();
            $table->timestamps();

            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_assets');
    }
};
