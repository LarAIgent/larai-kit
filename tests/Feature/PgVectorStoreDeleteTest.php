<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Support\Facades\DB;
use LarAIgent\AiKit\Services\VectorStore\PgVectorStore;
use LarAIgent\AiKit\Tests\TestCase;

class PgVectorStoreDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->run();
    }

    public function test_it_deletes_documents_by_chunk_id_in_metadata_and_id_fallback(): void
    {
        DB::table('ai_documents')->insert([
            [
                'id' => 11,
                'content' => 'keep me',
                'source_meta' => json_encode(['chunk_id' => 999]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'content' => 'delete by metadata',
                'source_meta' => json_encode(['chunk_id' => 555]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 77,
                'content' => 'delete by id fallback',
                'source_meta' => json_encode(['chunk_id' => 321]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        (new PgVectorStore())->delete([555, 77]);

        $this->assertDatabaseHas('ai_documents', ['id' => 11]);
        $this->assertDatabaseMissing('ai_documents', ['id' => 12]);
        $this->assertDatabaseMissing('ai_documents', ['id' => 77]);
    }
}
