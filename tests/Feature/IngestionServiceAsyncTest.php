<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Jobs\FetchUrlAssetJob;
use LarAIgent\AiKit\Jobs\ParseAssetJob;
use LarAIgent\AiKit\Jobs\ProcessTextAssetJob;
use LarAIgent\AiKit\Services\Ingestion\IngestionService;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use LarAIgent\AiKit\Services\Ingestion\UrlFetcher;
use LarAIgent\AiKit\Tests\TestCase;
use RuntimeException;

class IngestionServiceAsyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->run();
        $this->preparePublicDisk();
    }

    public function test_ingest_from_disk_dispatches_parse_job(): void
    {
        Storage::disk('public')->put('kb/policy.txt', 'Returns are accepted within 30 days.');
        Queue::fake();

        $service = app(IngestionService::class);
        $asset = $service->ingestFromDisk('public', 'kb/policy.txt', ['chatbot_id' => 42]);

        Queue::assertPushed(ParseAssetJob::class, function (ParseAssetJob $job) use ($asset) {
            return $job->asset->id === $asset->id
                && $job->scope['chatbot_id'] === 42;
        });

        $this->assertSame('queued', $asset->ingestion->state);
        $this->assertSame('file', $asset->source_type);
        $this->assertSame('public', $asset->source_disk);
        $this->assertSame('kb/policy.txt', $asset->source_path);
    }

    public function test_ingest_from_disk_throws_for_missing_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found on disk [public]: kb/missing.txt');

        app(IngestionService::class)->ingestFromDisk('public', 'kb/missing.txt');
    }

    public function test_ingest_from_disk_rejects_unsupported_mime(): void
    {
        Storage::disk('public')->put('kb/policy.txt', 'text');
        config(['larai-kit.allowed_mime_types' => ['application/pdf']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File type not allowed');

        app(IngestionService::class)->ingestFromDisk('public', 'kb/policy.txt');
    }

    public function test_ingest_text_dispatches_async_processing_job(): void
    {
        Queue::fake();

        $asset = app(IngestionService::class)->ingestText('Hello world', 'Notes', ['chatbot_id' => 10]);

        Queue::assertPushed(ProcessTextAssetJob::class, function (ProcessTextAssetJob $job) use ($asset) {
            return $job->asset->id === $asset->id
                && $job->content === 'Hello world'
                && $job->scope['chatbot_id'] === 10;
        });

        $this->assertDatabaseCount('ai_chunks', 0);
        $this->assertSame('queued', $asset->ingestion->state);
    }

    public function test_ingest_url_dispatches_fetch_job_after_safety_validation(): void
    {
        Queue::fake();

        $fetcher = new class extends UrlFetcher {
            public bool $validated = false;

            public function assertSafe(string $url): void
            {
                $this->validated = true;
            }
        };

        $service = new IngestionService(
            app(FileStorage::class),
            app(ParserRegistry::class),
            $fetcher,
        );

        $asset = $service->ingestUrl('https://example.com/docs/faq', ['chatbot_id' => 99]);

        $this->assertTrue($fetcher->validated);
        Queue::assertPushed(FetchUrlAssetJob::class, function (FetchUrlAssetJob $job) use ($asset) {
            return $job->asset->id === $asset->id
                && $job->url === 'https://example.com/docs/faq'
                && $job->scope['chatbot_id'] === 99;
        });

        $this->assertDatabaseCount('ai_chunks', 0);
        $this->assertSame('queued', $asset->ingestion->state);
    }

    protected function preparePublicDisk(): void
    {
        $root = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'larai-kit-public-test';
        File::ensureDirectoryExists($root);
        File::cleanDirectory($root);

        config([
            'filesystems.disks.public' => [
                'driver' => 'local',
                'root' => $root,
                'url' => 'http://localhost/storage',
                'visibility' => 'public',
            ],
        ]);
    }
}
