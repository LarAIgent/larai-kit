<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use LarAIgent\AiKit\Jobs\DeleteAssetVectorsJob;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Tests\TestCase;

class AssetDeletionVectorCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->run();
    }

    public function test_deleting_asset_dispatches_vector_cleanup_with_chunk_ids(): void
    {
        Queue::fake();

        $asset = Asset::create([
            'source_name' => 'Policy',
            'source_type' => 'file',
        ]);

        $chunkA = Chunk::create([
            'asset_id' => $asset->id,
            'content' => 'chunk a',
            'chunk_index' => 0,
            'created_at' => now(),
        ]);
        $chunkB = Chunk::create([
            'asset_id' => $asset->id,
            'content' => 'chunk b',
            'chunk_index' => 1,
            'created_at' => now(),
        ]);

        $asset->delete();

        Queue::assertPushed(DeleteAssetVectorsJob::class, function (DeleteAssetVectorsJob $job) use ($asset, $chunkA, $chunkB) {
            $actual = $job->chunkIds;
            sort($actual);

            $expected = [$chunkA->id, $chunkB->id];
            sort($expected);

            return $job->assetId === $asset->id && $actual === $expected;
        });
    }

    public function test_deleting_asset_without_chunks_does_not_dispatch_cleanup_job(): void
    {
        Queue::fake();

        $asset = Asset::create([
            'source_name' => 'Empty',
            'source_type' => 'file',
        ]);

        $asset->delete();

        Queue::assertNotPushed(DeleteAssetVectorsJob::class);
    }
}
