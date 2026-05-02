<?php

namespace LarAIgent\AiKit\Tests\Feature;

use LarAIgent\AiKit\Jobs\FetchUrlAssetJob;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\UrlFetcher;
use LarAIgent\AiKit\Tests\TestCase;
use RuntimeException;

class FetchUrlAssetJobFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->run();
    }

    public function test_it_marks_ingestion_failed_when_fetch_fails(): void
    {
        $asset = Asset::create([
            'source_name' => 'faq',
            'source_type' => 'url',
            'source_url' => 'https://example.com/faq',
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        $job = new FetchUrlAssetJob($asset, $ingestion, 'https://example.com/faq');
        $fetcher = new class extends UrlFetcher {
            public function fetch(string $url): array
            {
                throw new RuntimeException('network timeout');
            }
        };

        try {
            $job->handle($fetcher);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('network timeout', $e->getMessage());
        }

        $ingestion->refresh();
        $this->assertSame('failed', $ingestion->state);
        $this->assertSame('network timeout', $ingestion->error);
    }
}
