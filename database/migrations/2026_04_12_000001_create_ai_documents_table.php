<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasVector = $this->pgvectorAvailable();

        if ($hasVector) {
            Schema::ensureVectorExtensionExists();
        }

        Schema::create('ai_documents', function (Blueprint $table) use ($hasVector) {
            $table->id();
            $table->text('content');
            if ($hasVector) {
                $table->vector('embedding', dimensions: config('larai-kit.embedding_dimensions', 1536))->nullable();
            }
            $table->string('source_name')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_url')->nullable();
            $table->json('source_meta')->nullable();
            $table->timestamps();
        });
    }

    private function pgvectorAvailable(): bool
    {
        if (config('database.default') !== 'pgsql') {
            return false;
        }

        try {
            $result = \Illuminate\Support\Facades\DB::select(
                "SELECT 1 FROM pg_available_extensions WHERE name = 'vector' LIMIT 1"
            );
            return ! empty($result);
        } catch (\Throwable) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_documents');
    }
};
