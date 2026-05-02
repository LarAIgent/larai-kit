<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LarAIgent\AiKit\Events\AssetIndexed;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Tests\TestCase;

class IngestionTerminalEventsAfterCommitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->run();
    }

    public function test_terminal_event_is_dispatched_after_commit(): void
    {
        Event::fake([AssetIndexed::class]);

        $asset = Asset::create([
            'source_name' => 'Policy',
            'source_type' => 'file',
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
            'chunk_count' => 1,
        ]);

        DB::beginTransaction();
        try {
            $ingestion->markState('indexed');
            Event::assertNotDispatched(AssetIndexed::class);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        Event::assertDispatched(AssetIndexed::class, function (AssetIndexed $event) use ($asset) {
            return $event->asset->id === $asset->id;
        });
    }
}
