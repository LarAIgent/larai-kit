<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasVector = false;
        if (config('database.default') === 'pgsql') {
            try {
                $result = \Illuminate\Support\Facades\DB::select(
                    "SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1"
                );
                $hasVector = ! empty($result);
            } catch (\Throwable) {}
        }

        Schema::create('ai_chunks', function (Blueprint $table) use ($hasVector) {
            $table->id();
            $table->foreignId('asset_id')
                ->constrained('ai_assets')
                ->cascadeOnDelete();
            $table->text('content');
            if ($hasVector) {
                $table->vector('embedding', dimensions: config('larai-kit.embedding_dimensions', 1536))->nullable();
            }
            $table->unsignedInteger('chunk_index')->default(0);
            $table->unsignedInteger('page')->nullable();
            $table->unsignedBigInteger('time_start_ms')->nullable();
            $table->unsignedBigInteger('time_end_ms')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chunks');
    }
};
